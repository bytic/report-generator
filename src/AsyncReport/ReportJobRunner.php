<?php

declare(strict_types=1);

namespace ByTIC\ReportGenerator\AsyncReport;

use ByTIC\ReportGenerator\AsyncReport\Storage\FileSystemPartialResultStore;
use ByTIC\ReportGenerator\AsyncReport\Storage\IterablePartialResultStoreInterface;
use ByTIC\ReportGenerator\AsyncReport\Storage\PartialResultStoreInterface;
use ByTIC\ReportGenerator\Report\AbstractReport;
use ByTIC\ReportGenerator\Report\DataProvider\ChunkedDataProviderInterface;
use ByTIC\ReportGenerator\Report\ReportInterface;
use ByTIC\ReportGenerator\Report\Writer\Spreadsheets\AbstractSpreadsheet;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Throwable;

/**
 * Processes one chunk of a chunked report job.
 *
 * ## Usage
 *
 * ```php
 * $runner = new ReportJobRunner(new FileSystemPartialResultStore());
 *
 * // First request – initialise the state and run chunk 0
 * $state = ReportJobState::initial(MyReport::class, $params);
 * $state = $runner->runChunk($state);
 * $token = ContinuationToken::encode($state, $secret);
 *
 * // Subsequent requests
 * $state = ContinuationToken::decode($token, $secret);
 * $state = $runner->runChunk($state);
 *
 * // When $state->isDone() the output file is at $state->outputPath
 * ```
 *
 * ## DataProvider support
 *
 * If the report's DataProvider implements {@see ChunkedDataProviderInterface}
 * the runner will call `setOffset()` / `setLimit()` on each chunk so the
 * provider only fetches one page from the database.
 *
 * Providers that do NOT implement the interface are still supported: the
 * runner fetches all rows in a single call and treats that as one chunk.
 */
class ReportJobRunner
{
    public function __construct(
        private readonly PartialResultStoreInterface $store,
        private readonly string $finalizationMemoryLimit = '512M',
        private readonly int $finalizationTimeLimit = 120,
    ) {
    }

    /**
     * Process the next chunk for the given state.
     *
     * Returns the updated state.  When all data has been processed the state
     * will have `status = done` and `outputPath` set to the final file path.
     */
    public function runChunk(ReportJobState $state): ReportJobState
    {
        if ($state->isDone()) {
            return $state;
        }

        $this->store->initJob($state->jobId);

        $memoryLimit = null;
        $timeLimit = null;
        try {
            $state = $this->processChunk($state, $memoryLimit, $timeLimit);
        } catch (Throwable $e) {
            $state->status = ReportJobState::STATUS_FAILED;
            $state->errorMessage = $e->getMessage();
        } finally {
            $restoreErrors = [];
            if ($memoryLimit !== null) {
                gc_collect_cycles();
                gc_mem_caches();
                if (ini_set('memory_limit', $memoryLimit) === false) {
                    $restoreErrors[] = 'Cannot restore the PHP memory limit.';
                }
            }
            if ($timeLimit !== null) {
                if (ini_set('max_execution_time', $timeLimit) === false) {
                    $restoreErrors[] = 'Cannot restore the PHP execution time limit.';
                }
            }
            if ($restoreErrors !== []) {
                if ($state->outputPath !== null && is_file($state->outputPath) && !unlink($state->outputPath)) {
                    $restoreErrors[] = "Cannot remove output file: {$state->outputPath}";
                }
                throw new \RuntimeException(implode(' ', $restoreErrors));
            }
        }

        return $state;
    }

    /**
     * Build a download {@see BinaryFileResponse} for a completed job.
     *
     * @throws \RuntimeException if the output file does not exist.
     */
    public function createDownloadResponse(ReportJobState $state): BinaryFileResponse
    {
        if (!$state->isDone() || $state->outputPath === null) {
            throw new \RuntimeException('Report is not done yet.');
        }
        if (!file_exists($state->outputPath)) {
            throw new \RuntimeException("Output file not found: {$state->outputPath}");
        }

        $response = new BinaryFileResponse($state->outputPath);
        $response->setContentDisposition(
            ResponseHeaderBag::DISPOSITION_ATTACHMENT,
            $state->outputFilename ?? basename($state->outputPath),
        );

        return $response;
    }

    // -------------------------------------------------------------------------
    // Private
    // -------------------------------------------------------------------------

    private function processChunk(ReportJobState $state, ?string &$memoryLimit, ?string &$timeLimit): ReportJobState
    {
        $reportClass = $state->reportClass;

        if (!class_exists($reportClass)) {
            throw new \InvalidArgumentException("Report class not found: {$reportClass}");
        }

        /** @var AbstractReport&ReportInterface $report */
        $report = new $reportClass($state->params);
        $provider = $report->getDataProvider();

        // ── Set total on the very first chunk ──────────────────────────────
        if ($state->processedRows === 0 && $state->totalRows === -1) {
            if ($provider instanceof ChunkedDataProviderInterface) {
                $state->totalRows = $provider->getTotalCount();
            }
        }

        // ── Apply offset / limit when the provider supports pagination ─────
        $isChunkedProvider = $provider instanceof ChunkedDataProviderInterface;
        if ($isChunkedProvider) {
            $provider->setOffset($state->processedRows);
            $provider->setLimit($state->chunkSize);
        }

        // ── Fetch rows ─────────────────────────────────────────────────────
        $rows = iterator_to_array($provider->getData(), false);

        // ── Persist rows ───────────────────────────────────────────────────
        $chunkIndex = (int) floor($state->processedRows / max(1, $state->chunkSize));
        $this->store->appendChunk($state->jobId, $chunkIndex, $rows);

        // ── Advance cursor ─────────────────────────────────────────────────
        $state->processedRows += count($rows);
        $rowCount = count($rows);
        unset($rows);

        // ── Determine if we are done ───────────────────────────────────────
        $isDone = false;
        if ($isChunkedProvider) {
            $isDone = $state->totalRows !== -1
                ? $state->processedRows >= $state->totalRows
                : $rowCount < $state->chunkSize;
        } else {
            // Non-chunked: all data was returned in one shot
            $isDone = true;
        }

        if ($isDone) {
            $this->raiseFinalizationLimits($memoryLimit, $timeLimit);
            $state = $this->finalizeJob($state, $report);
        }

        return $state;
    }

    /**
     * Assemble all stored rows into the final output file.
     */
    private function finalizeJob(ReportJobState $state, AbstractReport $report): ReportJobState
    {
        // Preserve custom preloadData() implementations, which accept only arrays.
        $preloadMethod = new \ReflectionMethod($report, 'preloadData');
        $canIterate = $this->store instanceof IterablePartialResultStoreInterface;
        if ($this->store instanceof FileSystemPartialResultStore) {
            $allRowsMethod = new \ReflectionMethod($this->store, 'getAllRows');
            $iterateMethod = new \ReflectionMethod($this->store, 'iterateRows');
            if ($allRowsMethod->getDeclaringClass()->getName() !== FileSystemPartialResultStore::class
                && $iterateMethod->getDeclaringClass()->getName() === FileSystemPartialResultStore::class) {
                $canIterate = false;
            }
        }
        if ($this->store instanceof IterablePartialResultStoreInterface
            && $canIterate && $preloadMethod->getDeclaringClass()->getName() === AbstractReport::class) {
            $report->preloadIterableData($this->store->iterateRows($state->jobId));
        } else {
            $report->preloadData($this->store->getAllRows($state->jobId));
        }
        $writer = $report->getWriter($state->format);
        $outputPath = $this->buildOutputPath($state);
        try {
            $writer->save($outputPath);
        } catch (Throwable $e) {
            if (is_file($outputPath) && !unlink($outputPath)) {
                throw new \RuntimeException("Cannot remove failed output file: {$outputPath}", 0, $e);
            }
            throw $e;
        } finally {
            if ($writer instanceof AbstractSpreadsheet) {
                $writer->releaseReportJobResources();
            }
        }

        $state->outputPath = $outputPath;
        $extension = $state->format === 'csv' ? 'csv' : 'xlsx';
        $state->outputFilename = $report->getDefinition()->getFileName() . '.' . $extension;
        if ($state->totalRows === -1) {
            $state->totalRows = $state->processedRows;
        }
        $state->status = ReportJobState::STATUS_DONE;

        return $state;
    }

    private function raiseFinalizationLimits(?string &$restoreMemoryLimit, ?string &$restoreTimeLimit): void
    {
        $memoryLimit = ini_get('memory_limit');
        if ($memoryLimit === false) {
            throw new \RuntimeException('Cannot read the PHP memory limit.');
        }
        $currentMemory = $this->memoryLimitInBytes($memoryLimit);
        $requestedMemory = $this->memoryLimitInBytes($this->finalizationMemoryLimit);
        if ($requestedMemory < 0) {
            throw new \InvalidArgumentException('The finalization memory limit must be finite.');
        }
        if ($currentMemory >= 0 && $currentMemory < $requestedMemory) {
            if (ini_set('memory_limit', $this->finalizationMemoryLimit) === false) {
                throw new \RuntimeException('Cannot increase the PHP memory limit for report finalization.');
            }
            $restoreMemoryLimit = $memoryLimit;
        }

        $timeLimit = ini_get('max_execution_time');
        if ($timeLimit === false) {
            throw new \RuntimeException('Cannot read the PHP execution time limit.');
        }
        if ($this->finalizationTimeLimit < 1) {
            throw new \InvalidArgumentException('The finalization time limit must be positive.');
        }
        if ((int) $timeLimit > 0 && (int) $timeLimit < $this->finalizationTimeLimit) {
            if (ini_set('max_execution_time', (string) $this->finalizationTimeLimit) === false) {
                throw new \RuntimeException('Cannot increase the PHP execution time limit for report finalization.');
            }
            $restoreTimeLimit = $timeLimit;
        }
    }

    private function memoryLimitInBytes(string $limit): int
    {
        if ($limit === '-1') {
            return -1;
        }
        if (!preg_match('/^(\d+)\s*([KMG]?)$/i', trim($limit), $matches)) {
            throw new \InvalidArgumentException("Invalid PHP memory limit: {$limit}");
        }
        $power = match (strtoupper($matches[2])) {
            'K' => 1,
            'M' => 2,
            'G' => 3,
            default => 0,
        };
        return (int) $matches[1] * (1024 ** $power);
    }

    private function buildOutputPath(ReportJobState $state): string
    {
        $extension = $state->format === 'csv' ? '.csv' : '.xlsx';
        $dir = sys_get_temp_dir() . '/report_output';
        if (!is_dir($dir) && !@mkdir($dir, 0700, true) && !is_dir($dir)) {
            throw new \RuntimeException("Cannot create output directory: {$dir}");
        }

        $path = $dir . '/' . $state->jobId . '_' . bin2hex(random_bytes(16)) . $extension;
        $file = @fopen($path, 'x');
        if ($file === false) {
            throw new \RuntimeException("Cannot exclusively create output file: {$path}");
        }
        fclose($file);

        return $path;
    }
}

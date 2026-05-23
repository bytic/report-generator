<?php

declare(strict_types=1);

namespace ByTIC\ReportGenerator\AsyncReport;

use ByTIC\ReportGenerator\AsyncReport\Storage\PartialResultStoreInterface;
use ByTIC\ReportGenerator\Report\AbstractReport;
use ByTIC\ReportGenerator\Report\DataProvider\ChunkedDataProviderInterface;
use ByTIC\ReportGenerator\Report\ReportInterface;
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
        $this->store->initJob($state->jobId);

        try {
            $state = $this->processChunk($state);
        } catch (Throwable $e) {
            $state->status = ReportJobState::STATUS_FAILED;
            $state->errorMessage = $e->getMessage();
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
            basename($state->outputPath),
        );

        return $response;
    }

    // -------------------------------------------------------------------------
    // Private
    // -------------------------------------------------------------------------

    private function processChunk(ReportJobState $state): ReportJobState
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

        // ── Determine if we are done ───────────────────────────────────────
        $isDone = false;
        if ($isChunkedProvider) {
            $isDone = $state->totalRows !== -1
                ? $state->processedRows >= $state->totalRows
                : count($rows) < $state->chunkSize;
        } else {
            // Non-chunked: all data was returned in one shot
            $isDone = true;
        }

        if ($isDone) {
            $state = $this->finalizeJob($state, $report);
        }

        return $state;
    }

    /**
     * Assemble all stored rows into the final output file.
     */
    private function finalizeJob(ReportJobState $state, AbstractReport $report): ReportJobState
    {
        $allRows = $this->store->getAllRows($state->jobId);

        $outputPath = $this->buildOutputPath($state);

        // Re-use the report's own writer pipeline; pre-load the accumulated
        // rows so that the writer does not call getData() / DataProvider again.
        $report->preloadData($allRows);
        $writer = $report->getWriter($state->format);
        $writer->save($outputPath);

        $state->outputPath = $outputPath;
        $state->processedRows = count($allRows);
        if ($state->totalRows === -1) {
            $state->totalRows = $state->processedRows;
        }
        $state->status = ReportJobState::STATUS_DONE;

        return $state;
    }

    private function buildOutputPath(ReportJobState $state): string
    {
        $extension = $state->format === 'csv' ? '.csv' : '.xlsx';
        $dir = sys_get_temp_dir() . '/report_output';
        if (!is_dir($dir) && !mkdir($dir, 0700, true) && !is_dir($dir)) {
            throw new \RuntimeException("Cannot create output directory: {$dir}");
        }

        return $dir . '/' . $state->jobId . $extension;
    }
}

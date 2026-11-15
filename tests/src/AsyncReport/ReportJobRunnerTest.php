<?php

declare(strict_types=1);

namespace ByTIC\ReportGenerator\Tests\AsyncReport;

use ByTIC\ReportGenerator\AsyncReport\ReportJobRunner;
use ByTIC\ReportGenerator\AsyncReport\ReportJobState;
use ByTIC\ReportGenerator\AsyncReport\Storage\FileSystemPartialResultStore;
use ByTIC\ReportGenerator\Report\AbstractReport;
use ByTIC\ReportGenerator\Report\DataProvider\AbstractDataProvider;
use ByTIC\ReportGenerator\Report\DataProvider\ChunkedDataProviderInterface;
use ByTIC\ReportGenerator\Report\ReportInterface;
use ByTIC\ReportGenerator\Report\Writer\Xlsx;
use ByTIC\ReportGenerator\Tests\Fixtures\BasicReport\Report;
use PHPUnit\Framework\TestCase;

/**
 * @covers \ByTIC\ReportGenerator\AsyncReport\ReportJobRunner
 */
class ReportJobRunnerTest extends TestCase
{
    private string $tmpDir;
    private FileSystemPartialResultStore $store;
    private ReportJobRunner $runner;
    private array $outputPaths = [];

    protected function setUp(): void
    {
        parent::setUp();

        $this->tmpDir = sys_get_temp_dir() . '/rg_test_' . bin2hex(random_bytes(8));
        $this->store = new FileSystemPartialResultStore($this->tmpDir);
        $this->runner = new ReportJobRunner($this->store);
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        $this->removeTempDir($this->tmpDir);
        foreach ($this->outputPaths as $path) {
            if (is_file($path)) {
                unlink($path);
            }
        }
    }

    // -------------------------------------------------------------------------

    /**
     * Full integration test: requires bytic/collections to be installed.
     * Skipped automatically when the package is not available.
     */
    public function test_run_single_chunk_for_non_chunked_provider(): void
    {
        $this->requireNipCollections();

        $state = ReportJobState::initial(Report::class, [], 'xlsx', 500);
        $state = $this->runner->runChunk($state);

        self::assertTrue($state->isDone(), 'Non-chunked provider should be done after one runChunk()');
        self::assertNotNull($state->outputPath);
        self::assertFileExists((string) $state->outputPath);
        // BasicReport yields 2 rows
        self::assertSame(2, $state->processedRows);
    }

    public function test_output_file_is_xlsx(): void
    {
        $this->requireNipCollections();

        $state = ReportJobState::initial(Report::class, [], 'xlsx', 500);
        $state = $this->runner->runChunk($state);

        self::assertStringEndsWith('.xlsx', (string) $state->outputPath);
    }

    public function test_state_status_is_done_after_completion(): void
    {
        $this->requireNipCollections();

        $state = ReportJobState::initial(Report::class, [], 'xlsx', 500);
        $state = $this->runner->runChunk($state);

        self::assertSame(ReportJobState::STATUS_DONE, $state->status);
    }

    public function test_invalid_report_class_sets_failed_status(): void
    {
        $state = ReportJobState::initial('App\\Reports\\NonExistentReport', [], 'xlsx', 500);
        $state = $this->runner->runChunk($state);

        self::assertTrue($state->isFailed());
        self::assertNotEmpty($state->errorMessage);
    }

    public function test_create_download_response_requires_done_state(): void
    {
        $state = ReportJobState::initial(Report::class, [], 'xlsx', 500);

        $this->expectException(\RuntimeException::class);
        $this->runner->createDownloadResponse($state);
    }

    public function test_create_download_response_for_completed_job(): void
    {
        $this->requireNipCollections();

        $state = ReportJobState::initial(Report::class, [], 'xlsx', 500);
        $state = $this->runner->runChunk($state);

        $response = $this->runner->createDownloadResponse($state);

        self::assertSame(200, $response->getStatusCode());
        self::assertStringContainsString(
            'attachment',
            (string) $response->headers->get('Content-Disposition'),
        );
        self::assertStringContainsString(
            'Total Report.xlsx',
            (string) $response->headers->get('Content-Disposition'),
        );
    }

    public function test_same_report_exports_have_independent_files_and_cleanup(): void
    {
        $this->requireNipCollections();

        $first = ReportJobState::initial(Report::class, [], 'csv');
        $second = ReportJobState::initial(Report::class, [], 'csv');
        self::assertNotSame($first->jobId, $second->jobId);

        $first = $this->runner->runChunk($first);
        $second = $this->runner->runChunk($second);
        self::assertTrue($first->isDone(), (string) $first->errorMessage);
        self::assertTrue($second->isDone(), (string) $second->errorMessage);
        $this->outputPaths = [$first->outputPath, $second->outputPath];

        self::assertNotSame($first->outputPath, $second->outputPath);
        self::assertSame($first->outputFilename, $second->outputFilename);
        $contents = file_get_contents($second->outputPath);

        $this->store->cleanup($first->jobId);
        unlink($first->outputPath);

        self::assertTrue($this->store->hasJob($second->jobId));
        self::assertSame($contents, file_get_contents($second->outputPath));
        self::assertSame(200, $this->runner->createDownloadResponse($second)->getStatusCode());
    }

    public function test_replayed_job_does_not_overwrite_existing_output(): void
    {
        $this->requireNipCollections();

        $initial = ReportJobState::initial(Report::class, [], 'csv');
        $first = $this->runner->runChunk(clone $initial);
        self::assertTrue($first->isDone(), (string) $first->errorMessage);
        $this->outputPaths[] = $first->outputPath;
        $contents = file_get_contents($first->outputPath);

        $second = $this->runner->runChunk(clone $initial);
        self::assertTrue($second->isDone(), (string) $second->errorMessage);
        $this->outputPaths[] = $second->outputPath;

        self::assertNotSame($first->outputPath, $second->outputPath);
        self::assertSame($contents, file_get_contents($first->outputPath));
    }

    public function test_parallel_exports_have_independent_output_files(): void
    {
        $this->requireNipCollections();
        $script = <<<'PHP'
require $argv[1];
$runner = new \ByTIC\ReportGenerator\AsyncReport\ReportJobRunner(
    new \ByTIC\ReportGenerator\AsyncReport\Storage\FileSystemPartialResultStore($argv[2]),
);
$state = \ByTIC\ReportGenerator\AsyncReport\ReportJobState::initial(
    \ByTIC\ReportGenerator\Tests\Fixtures\BasicReport\Report::class,
    [],
    'csv',
);
echo json_encode($runner->runChunk($state)->toArray(), JSON_THROW_ON_ERROR);
PHP;
        $command = [PHP_BINARY, '-r', $script, PROJECT_BASE_PATH . '/vendor/autoload.php', $this->tmpDir];
        $processes = [];
        for ($i = 0; $i < 2; ++$i) {
            $process = proc_open($command, [1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes);
            self::assertIsResource($process);
            $processes[] = [$process, $pipes];
        }

        $states = [];
        foreach ($processes as [$process, $pipes]) {
            $output = stream_get_contents($pipes[1]);
            $error = stream_get_contents($pipes[2]);
            fclose($pipes[1]);
            fclose($pipes[2]);
            self::assertSame(0, proc_close($process), $error);
            $state = ReportJobState::fromArray(json_decode($output, true, 512, JSON_THROW_ON_ERROR));
            self::assertTrue($state->isDone(), (string) $state->errorMessage);
            $this->outputPaths[] = $state->outputPath;
            self::assertFileExists($state->outputPath);
            $states[] = $state;
        }

        self::assertNotSame($states[0]->jobId, $states[1]->jobId);
        self::assertNotSame($states[0]->outputPath, $states[1]->outputPath);
        self::assertSame($states[0]->outputFilename, $states[1]->outputFilename);
    }

    public function test_completed_job_is_not_processed_again(): void
    {
        $state = ReportJobState::initial('App\\Reports\\NonExistentReport', []);
        $state->status = ReportJobState::STATUS_DONE;

        self::assertSame($state, $this->runner->runChunk($state));
        self::assertFalse($this->store->hasJob($state->jobId));
    }

    public function test_large_chunked_xlsx_uses_iterable_rows_and_temporary_limits(): void
    {
        $store = new class($this->tmpDir) extends FileSystemPartialResultStore {
            public function getAllRows(string $jobId): array
            {
                throw new \RuntimeException('Rows must be read incrementally.');
            }

            public function iterateRows(string $jobId): iterable
            {
                yield from parent::iterateRows($jobId);
            }
        };
        $runner = new ReportJobRunner($store);
        $memory = ini_get('memory_limit');
        $time = ini_get('max_execution_time');
        try {
            ini_set('memory_limit', '128M');
            ini_set('max_execution_time', '30');
            $state = ReportJobState::initial(LargeChunkedReport::class, [], 'xlsx', 500);
            do {
                $state = $runner->runChunk($state);
                self::assertFalse($state->isFailed(), (string) $state->errorMessage);
                self::assertSame('128M', ini_get('memory_limit'));
                self::assertSame('30', ini_get('max_execution_time'));
            } while (!$state->isDone());

            $this->outputPaths[] = $state->outputPath;
            self::assertSame(3000, $state->processedRows);
            self::assertSame(['512M', '120'], LimitsRecordingXlsx::$limits);
            self::assertFileExists($state->outputPath);
            self::assertGreaterThan(1000, filesize($state->outputPath));
            $archive = new \PharData($state->outputPath);
            $xml = simplexml_load_string($archive['xl/worksheets/sheet1.xml']->getContent());
            self::assertCount(3001, $xml->sheetData->row);
            self::assertSame('0', (string) $xml->sheetData->row[1]->c[0]->v);
            self::assertSame('74999', (string) $xml->sheetData->row[3000]->c[24]->v);
        } finally {
            ini_set('memory_limit', $memory);
            ini_set('max_execution_time', $time);
        }
    }

    public function test_finalization_does_not_lower_higher_limits(): void
    {
        $memory = ini_get('memory_limit');
        $time = ini_get('max_execution_time');
        try {
            ini_set('memory_limit', '1G');
            ini_set('max_execution_time', '180');
            $state = ReportJobState::initial(LargeChunkedReport::class, [], 'xlsx', 3000);
            $state = $this->runner->runChunk($state);

            self::assertTrue($state->isDone(), (string) $state->errorMessage);
            $this->outputPaths[] = $state->outputPath;
            self::assertSame(['1G', '180'], LimitsRecordingXlsx::$limits);
        } finally {
            ini_set('memory_limit', $memory);
            ini_set('max_execution_time', $time);
        }
    }

    public function test_limits_are_restored_after_finalization_failure(): void
    {
        $memory = ini_get('memory_limit');
        $time = ini_get('max_execution_time');
        try {
            ini_set('memory_limit', '128M');
            ini_set('max_execution_time', '30');
            $state = ReportJobState::initial(FailingChunkedReport::class, [], 'xlsx', 3000);
            $state = $this->runner->runChunk($state);

            self::assertTrue($state->isFailed());
            self::assertSame('Writer failed', $state->errorMessage);
            self::assertSame('128M', ini_get('memory_limit'));
            self::assertSame('30', ini_get('max_execution_time'));
        } finally {
            ini_set('memory_limit', $memory);
            ini_set('max_execution_time', $time);
        }
    }

    public function test_custom_preload_data_is_preserved(): void
    {
        CustomPreloadReport::$preloaded = false;
        $state = ReportJobState::initial(CustomPreloadReport::class, [], 'xlsx', 3000);
        $state = $this->runner->runChunk($state);
        self::assertTrue($state->isDone(), (string) $state->errorMessage);
        $this->outputPaths[] = $state->outputPath;
        self::assertTrue(CustomPreloadReport::$preloaded);
    }

    public function test_store_subclass_get_all_rows_override_is_preserved(): void
    {
        $store = new class($this->tmpDir) extends FileSystemPartialResultStore {
            public bool $called = false;

            public function getAllRows(string $jobId): array
            {
                $this->called = true;
                return parent::getAllRows($jobId);
            }
        };
        $runner = new ReportJobRunner($store);
        $state = $runner->runChunk(ReportJobState::initial(Report::class, [], 'csv'));
        self::assertTrue($state->isDone(), (string) $state->errorMessage);
        $this->outputPaths[] = $state->outputPath;
        self::assertTrue($store->called);
    }

    public function test_invalid_time_limit_restores_an_already_raised_memory_limit(): void
    {
        $memory = ini_get('memory_limit');
        try {
            ini_set('memory_limit', '128M');
            $runner = new ReportJobRunner($this->store, '512M', 0);
            $state = $runner->runChunk(ReportJobState::initial(Report::class, [], 'csv'));
            self::assertTrue($state->isFailed());
            self::assertSame('The finalization time limit must be positive.', $state->errorMessage);
            self::assertSame('128M', ini_get('memory_limit'));
        } finally {
            ini_set('memory_limit', $memory);
        }
    }

    // -------------------------------------------------------------------------

    /** Skip the test if bytic/collections is not installed. */
    private function requireNipCollections(): void
    {
        if (!class_exists(\Nip\Collections\Typed\ClassCollection::class)) {
            $this->markTestSkipped('bytic/collections is not installed – skipping integration test.');
        }
    }

    private function removeTempDir(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }
        $items = scandir($dir);
        if ($items === false) {
            return;
        }
        foreach ($items as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }
            $path = $dir . '/' . $item;
            is_dir($path) ? $this->removeTempDir($path) : @unlink($path);
        }
        @rmdir($dir);
    }
}

class LargeChunkedReport extends AbstractReport implements ReportInterface
{
    protected function define(): void
    {
        $this->getDefinition()->setTitle('Large report');
        for ($i = 0; $i < 25; ++$i) {
            $this->getDefinition()->addColumnSimple('column' . $i);
        }
    }

    protected function getDataProviderClass(): string
    {
        return LargeChunkedProvider::class;
    }

    protected function generateWriter($type)
    {
        return new LimitsRecordingXlsx($this);
    }
}

class LargeChunkedProvider extends AbstractDataProvider implements ChunkedDataProviderInterface
{
    private int $offset = 0;
    private int $limit = 0;

    public function setOffset(int $offset): void
    {
        $this->offset = $offset;
    }

    public function setLimit(int $limit): void
    {
        $this->limit = $limit;
    }

    public function getTotalCount(): int
    {
        return 3000;
    }

    protected function generateData(): \Generator
    {
        for ($i = $this->offset; $i < min(3000, $this->offset + $this->limit); ++$i) {
            $values = [];
            for ($column = 0; $column < 25; ++$column) {
                $values['column' . $column] = $i * 25 + $column;
            }
            yield $this->newDataRow($values);
        }
    }
}

class LimitsRecordingXlsx extends Xlsx
{
    public static array $limits = [];

    public function save($name)
    {
        self::$limits = [ini_get('memory_limit'), ini_get('max_execution_time')];
        parent::save($name);
    }
}

class FailingChunkedReport extends LargeChunkedReport
{
    protected function generateWriter($type)
    {
        return new class($this) extends Xlsx {
            public function save($name)
            {
                throw new \RuntimeException('Writer failed');
            }
        };
    }
}

class CustomPreloadReport extends LargeChunkedReport
{
    public static bool $preloaded = false;

    public function preloadData(array $rows): void
    {
        self::$preloaded = true;
        parent::preloadData($rows);
    }
}

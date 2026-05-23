<?php

declare(strict_types=1);

namespace ByTIC\ReportGenerator\Tests\AsyncReport;

use ByTIC\ReportGenerator\AsyncReport\ReportJobRunner;
use ByTIC\ReportGenerator\AsyncReport\ReportJobState;
use ByTIC\ReportGenerator\AsyncReport\Storage\FileSystemPartialResultStore;
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

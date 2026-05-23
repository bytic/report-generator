<?php

declare(strict_types=1);

namespace ByTIC\ReportGenerator\Tests\AsyncReport;

use ByTIC\ReportGenerator\AsyncReport\Storage\FileSystemPartialResultStore;
use PHPUnit\Framework\TestCase;
use stdClass;

/**
 * @covers \ByTIC\ReportGenerator\AsyncReport\Storage\FileSystemPartialResultStore
 */
class FileSystemPartialResultStoreTest extends TestCase
{
    private string $tmpDir;
    private FileSystemPartialResultStore $store;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tmpDir = sys_get_temp_dir() . '/rg_store_test_' . bin2hex(random_bytes(8));
        $this->store = new FileSystemPartialResultStore($this->tmpDir);
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        $this->removeTempDir($this->tmpDir);
    }

    // -------------------------------------------------------------------------

    public function test_init_job_creates_directory(): void
    {
        $jobId = 'test-job-1';
        $this->store->initJob($jobId);

        self::assertDirectoryExists($this->tmpDir . '/' . $jobId);
    }

    public function test_init_job_is_idempotent(): void
    {
        $jobId = 'test-job-idempotent';
        $this->store->initJob($jobId);
        $this->store->initJob($jobId);  // second call must not throw

        self::assertDirectoryExists($this->tmpDir . '/' . $jobId);
    }

    public function test_has_job_returns_false_before_init(): void
    {
        self::assertFalse($this->store->hasJob('non-existent-job'));
    }

    public function test_has_job_returns_true_after_init(): void
    {
        $jobId = 'test-job-has';
        $this->store->initJob($jobId);

        self::assertTrue($this->store->hasJob($jobId));
    }

    public function test_append_and_retrieve_single_chunk(): void
    {
        $jobId = 'test-job-single';
        $rows = [new stdClass(), new stdClass()];
        $rows[0]->name = 'Alice';
        $rows[1]->name = 'Bob';

        $this->store->appendChunk($jobId, 0, $rows);
        $retrieved = $this->store->getAllRows($jobId);

        self::assertCount(2, $retrieved);
        self::assertSame('Alice', $retrieved[0]->name);
        self::assertSame('Bob', $retrieved[1]->name);
    }

    public function test_append_multiple_chunks_preserves_order(): void
    {
        $jobId = 'test-job-multi';

        $chunk0 = [(object) ['order' => 0], (object) ['order' => 1]];
        $chunk1 = [(object) ['order' => 2], (object) ['order' => 3]];
        $chunk2 = [(object) ['order' => 4]];

        $this->store->appendChunk($jobId, 0, $chunk0);
        $this->store->appendChunk($jobId, 2, $chunk2);  // intentionally out of order
        $this->store->appendChunk($jobId, 1, $chunk1);

        $all = $this->store->getAllRows($jobId);

        self::assertCount(5, $all);
        // Rows must be sorted by chunk index: 0, 1, 2
        self::assertSame(0, $all[0]->order);
        self::assertSame(1, $all[1]->order);
        self::assertSame(2, $all[2]->order);
        self::assertSame(3, $all[3]->order);
        self::assertSame(4, $all[4]->order);
    }

    public function test_get_all_rows_returns_empty_for_non_existent_job(): void
    {
        $all = $this->store->getAllRows('no-such-job');
        self::assertSame([], $all);
    }

    public function test_cleanup_removes_directory(): void
    {
        $jobId = 'test-job-cleanup';
        $this->store->appendChunk($jobId, 0, [(object) ['x' => 1]]);

        self::assertDirectoryExists($this->tmpDir . '/' . $jobId);

        $this->store->cleanup($jobId);

        self::assertDirectoryDoesNotExist($this->tmpDir . '/' . $jobId);
    }

    public function test_cleanup_is_safe_for_non_existent_job(): void
    {
        $this->store->cleanup('never-existed');
        $this->addToAssertionCount(1); // no exception = OK
    }

    // -------------------------------------------------------------------------

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

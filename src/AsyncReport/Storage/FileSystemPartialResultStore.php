<?php

declare(strict_types=1);

namespace ByTIC\ReportGenerator\AsyncReport\Storage;

use ByTIC\ReportGenerator\Report\DataProvider\DataRows\DataRow;
use RuntimeException;

/**
 * Stores chunked report rows in the local filesystem.
 *
 * Each chunk is written to a separate file inside a job-specific temporary
 * directory.  On finalisation the caller reads all chunks back via
 * `getAllRows()`.
 *
 * Directory layout:
 *   {baseDir}/{jobId}/chunk_0.dat
 *   {baseDir}/{jobId}/chunk_1.dat
 *   ...
 */
class FileSystemPartialResultStore implements PartialResultStoreInterface
{
    private readonly string $baseDir;

    public function __construct(string $baseDir = '')
    {
        $this->baseDir = rtrim($baseDir !== '' ? $baseDir : sys_get_temp_dir() . '/report_chunks', '/');
    }

    // -------------------------------------------------------------------------
    // PartialResultStoreInterface
    // -------------------------------------------------------------------------

    public function initJob(string $jobId): void
    {
        $dir = $this->jobDir($jobId);
        if (!is_dir($dir) && !mkdir($dir, 0700, true) && !is_dir($dir)) {
            throw new RuntimeException("Cannot create job directory: {$dir}");
        }
    }

    /**
     * @param DataRow[] $rows
     */
    public function appendChunk(string $jobId, int $chunkIndex, array $rows): void
    {
        $this->initJob($jobId);

        $file = $this->chunkFile($jobId, $chunkIndex);
        $encoded = base64_encode(serialize($rows));

        if (file_put_contents($file, $encoded) === false) {
            throw new RuntimeException("Cannot write chunk file: {$file}");
        }
    }

    /**
     * @return DataRow[]
     */
    public function getAllRows(string $jobId): array
    {
        $dir = $this->jobDir($jobId);
        if (!is_dir($dir)) {
            return [];
        }

        $files = glob($dir . '/chunk_*.dat');
        if ($files === false || $files === []) {
            return [];
        }

        // Sort by chunk index (chunk_0, chunk_1, …)
        usort($files, static function (string $a, string $b): int {
            return self::chunkIndexFromPath($a) <=> self::chunkIndexFromPath($b);
        });

        $all = [];
        foreach ($files as $file) {
            $content = file_get_contents($file);
            if ($content === false) {
                throw new RuntimeException("Cannot read chunk file: {$file}");
            }
            /** @var mixed $rows */
            $rows = unserialize(base64_decode($content));
            if (!is_array($rows)) {
                throw new RuntimeException("Corrupt chunk file (expected array): {$file}");
            }
            foreach ($rows as $row) {
                if (!is_object($row)) {
                    throw new RuntimeException("Corrupt chunk file (expected object rows): {$file}");
                }
                $all[] = $row;
            }
        }

        return $all;
    }

    public function hasJob(string $jobId): bool
    {
        return is_dir($this->jobDir($jobId));
    }

    public function cleanup(string $jobId): void
    {
        $dir = $this->jobDir($jobId);
        if (!is_dir($dir)) {
            return;
        }
        $this->removeDirectory($dir);
    }

    // -------------------------------------------------------------------------
    // Private helpers
    // -------------------------------------------------------------------------

    private function jobDir(string $jobId): string
    {
        return $this->baseDir . '/' . $jobId;
    }

    private function chunkFile(string $jobId, int $chunkIndex): string
    {
        return $this->jobDir($jobId) . '/chunk_' . $chunkIndex . '.dat';
    }

    private static function chunkIndexFromPath(string $path): int
    {
        $base = basename($path, '.dat');   // chunk_N
        return (int) substr($base, 6);     // strip "chunk_"
    }

    private function removeDirectory(string $dir): void
    {
        $items = scandir($dir);
        if ($items === false) {
            return;
        }
        foreach ($items as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }
            $path = $dir . '/' . $item;
            if (is_dir($path)) {
                $this->removeDirectory($path);
            } else {
                @unlink($path);
            }
        }
        @rmdir($dir);
    }
}

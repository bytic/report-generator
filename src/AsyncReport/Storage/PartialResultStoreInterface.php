<?php

declare(strict_types=1);

namespace ByTIC\ReportGenerator\AsyncReport\Storage;

use ByTIC\ReportGenerator\Report\DataProvider\DataRows\DataRow;

/**
 * Persists the DataRow objects produced by individual chunks so they can be
 * assembled into a final output file once all chunks have been processed.
 */
interface PartialResultStoreInterface
{
    /**
     * Initialise storage for a new job.
     * Safe to call multiple times (idempotent).
     */
    public function initJob(string $jobId): void;

    /**
     * Persist one chunk of rows.
     *
     * @param DataRow[] $rows
     */
    public function appendChunk(string $jobId, int $chunkIndex, array $rows): void;

    /**
     * Return all stored rows in insertion order.
     *
     * @return DataRow[]
     */
    public function getAllRows(string $jobId): array;

    /**
     * Return true if the job storage directory / key exists.
     */
    public function hasJob(string $jobId): bool;

    /**
     * Remove all temporary files / keys for this job.
     * Safe to call even if the job does not exist.
     */
    public function cleanup(string $jobId): void;
}

<?php

declare(strict_types=1);

namespace ByTIC\ReportGenerator\Report\DataProvider;

/**
 * Implemented by DataProviders that support server-side pagination.
 *
 * When a DataProvider implements this interface the ReportJobRunner will
 * call `setOffset()` / `setLimit()` before each chunk so that
 * `generateData()` only fetches the relevant page of rows.
 *
 * Providers that do NOT implement this interface are still supported:
 * the runner fetches all rows in a single pass and treats that as one chunk.
 */
interface ChunkedDataProviderInterface
{
    /**
     * Skip this many rows before returning data.
     */
    public function setOffset(int $offset): void;

    /**
     * Return at most this many rows.
     */
    public function setLimit(int $limit): void;

    /**
     * Return the total number of rows in the full (unpaginated) result.
     * Return -1 if the count is unavailable or too expensive to compute.
     */
    public function getTotalCount(): int;
}

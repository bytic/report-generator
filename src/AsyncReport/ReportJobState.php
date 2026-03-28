<?php

declare(strict_types=1);

namespace ByTIC\ReportGenerator\AsyncReport;

/**
 * Serialisable snapshot of a chunked report job.
 *
 * This object is encoded into the continuation token that travels between
 * HTTP requests.  Every field must be JSON-serialisable (no objects, no
 * circular references).  Application-level params must therefore only contain
 * primitive values (strings, ints, floats, booleans, or plain arrays of the
 * same).
 */
class ReportJobState
{
    public const STATUS_PROCESSING = 'processing';
    public const STATUS_DONE = 'done';
    public const STATUS_FAILED = 'failed';

    public string $jobId;

    /** @var self::STATUS_* */
    public string $status;

    /** Fully-qualified class name of the report to run. */
    public string $reportClass;

    /**
     * Parameters forwarded to the report constructor.
     * Must contain only JSON-serialisable primitives.
     *
     * @var array<string, mixed>
     */
    public array $params;

    /** Output format: 'xlsx' or 'csv'. */
    public string $format;

    /** Rows to process per HTTP request. */
    public int $chunkSize;

    /** Total rows in the full dataset; -1 means unknown. */
    public int $totalRows;

    /** Rows already processed across previous requests. */
    public int $processedRows;

    /** Absolute path to the final output file (set when status = done). */
    public ?string $outputPath;

    /** Human-readable error message (set when status = failed). */
    public ?string $errorMessage;

    // -------------------------------------------------------------------------
    // Factory helpers
    // -------------------------------------------------------------------------

    /**
     * Create the initial state for a new job.
     *
     * @param array<string, mixed> $params
     */
    public static function initial(
        string $reportClass,
        array $params,
        string $format = 'xlsx',
        int $chunkSize = 500,
    ): self {
        $state = new self();
        $state->jobId = self::generateJobId();
        $state->status = self::STATUS_PROCESSING;
        $state->reportClass = $reportClass;
        $state->params = $params;
        $state->format = $format;
        $state->chunkSize = $chunkSize;
        $state->totalRows = -1;
        $state->processedRows = 0;
        $state->outputPath = null;
        $state->errorMessage = null;

        return $state;
    }

    // -------------------------------------------------------------------------
    // Serialisation
    // -------------------------------------------------------------------------

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'jobId' => $this->jobId,
            'status' => $this->status,
            'reportClass' => $this->reportClass,
            'params' => $this->params,
            'format' => $this->format,
            'chunkSize' => $this->chunkSize,
            'totalRows' => $this->totalRows,
            'processedRows' => $this->processedRows,
            'outputPath' => $this->outputPath,
            'errorMessage' => $this->errorMessage,
        ];
    }

    /** @param array<string, mixed> $data */
    public static function fromArray(array $data): self
    {
        $state = new self();
        $state->jobId = (string) $data['jobId'];
        $state->status = (string) $data['status'];
        $state->reportClass = (string) $data['reportClass'];
        $state->params = (array) ($data['params'] ?? []);
        $state->format = (string) ($data['format'] ?? 'xlsx');
        $state->chunkSize = (int) ($data['chunkSize'] ?? 500);
        $state->totalRows = (int) ($data['totalRows'] ?? -1);
        $state->processedRows = (int) ($data['processedRows'] ?? 0);
        $state->outputPath = isset($data['outputPath']) ? (string) $data['outputPath'] : null;
        $state->errorMessage = isset($data['errorMessage']) ? (string) $data['errorMessage'] : null;

        return $state;
    }

    // -------------------------------------------------------------------------
    // Convenience
    // -------------------------------------------------------------------------

    public function isDone(): bool
    {
        return $this->status === self::STATUS_DONE;
    }

    public function isFailed(): bool
    {
        return $this->status === self::STATUS_FAILED;
    }

    public function progressPercent(): ?int
    {
        if ($this->totalRows <= 0) {
            return null;
        }

        return (int) min(100, round($this->processedRows / $this->totalRows * 100));
    }

    // -------------------------------------------------------------------------
    // Private
    // -------------------------------------------------------------------------

    private static function generateJobId(): string
    {
        return bin2hex(random_bytes(16));
    }
}

# Chunked / Multi-Request Report Generation

## Problem Statement

The current model generates an entire report in a **single synchronous HTTP request**:

1. `AbstractReport::run()` calls `generateData()`, which pulls a PHP `Generator` from `AbstractDataProvider`
2. `AbstractSpreadsheet` iterates the generator row-by-row but **builds the entire PhpSpreadsheet object in RAM** before writing
3. The response is streamed only after the whole spreadsheet is assembled

For large exports this model fails because of:

| Constraint | Effect |
|---|---|
| PHP `max_execution_time` (default 30 s) | Request is killed mid-generation |
| PHP `memory_limit` (default 128 MB) | PhpSpreadsheet with millions of cells exhausts RAM |
| Complex per-row calculations | Multiply execution time linearly |
| Multiple chapters / perspectives | Each one adds its own full pass over a potentially heavy data set |

---

## Current Architecture – Quick Reference

```
AbstractReport
  ├─ run()                         ← single entry point
  │    ├─ validateDefinition()     ← calls define() once
  │    └─ generateData()           ← pulls Generator from DataProvider
  ├─ render()                      ← delegates to Writer
  │
  ├─ AbstractDataProvider
  │    └─ generateData(): Generator  ← abstract, yields DataRow objects
  │
  ├─ AbstractDefinition
  │    ├─ HeaderCollection          ← column definitions per chapter
  │    └─ ReportChaptersCollection  ← named worksheet sections
  │
  └─ AbstractWriter (AbstractSpreadsheet)
       ├─ generateSpreadsheet()     ← builds PhpSpreadsheet in memory
       └─ generateResponseContent() ← streams via StreamedResponse
```

Key facts that constrain the design:
- `AbstractDataProvider::generateData()` uses PHP `Generator` – this is already memory-efficient for **data retrieval** but not for **assembly**.
- `AbstractSpreadsheet` accumulates all rows into a `Spreadsheet` object before flushing; there is no row-streaming or file-chunking at the writer level.
- Params are passed through `HasParamsTrait`; state between request boundaries is not currently modeled.

---

## Proposed Approach

Two complementary strategies are described below. They can be implemented independently or combined.

### Strategy A – Queue-Based Asynchronous Generation (recommended for production)

The HTTP request merely **schedules** the job; actual generation happens in a background worker. The user polls (or is notified via webhook/SSE/websocket) and downloads when ready.

```
HTTP Request 1  →  ReportJobManager::schedule($report, $params)  →  returns jobId
                          │
                          ▼
                   [Queue / Message Bus]
                          │
                          ▼
                   ReportJobRunner::process($jobId)
                          │
                    ┌─────┴─────┐
                    │           │
               chunk 1       chunk N
                    │           │
                    └─────┬─────┘
                          ▼
                   PartialResultStore
                   (temp files / object storage)
                          │
                          ▼
                   FileAssembler::merge()
                          │
                          ▼
                   FinalResultStore  (S3 / filesystem)
                          │
HTTP Request 2  ←  ReportJobManager::getStatus($jobId)
HTTP Request 3  ←  ReportJobManager::download($jobId)
```

### Strategy B – Stateless Multi-Step Chunked Processing (no queue required)

Useful when no queue infrastructure is available (e.g., simple frameworks, serverless environments). Each HTTP request processes one **chunk** and returns a **continuation token**; the caller (browser JS, CLI loop, cron) drives the next request.

```
POST /reports/start            → { token: "abc123", total: 5000, processed: 0 }
POST /reports/continue/abc123  → { token: "abc123", total: 5000, processed: 1000 }
POST /reports/continue/abc123  → { token: "abc123", total: 5000, processed: 2000 }
...
POST /reports/finalize/abc123  → 302 Location: /reports/download/abc123
GET  /reports/download/abc123  → streamed file
```

---

## New Components

### 1. `ReportJobState` (Value Object)

```
src/AsyncReport/ReportJobState.php
```

Serializable DTO holding the current job snapshot:

```php
class ReportJobState
{
    public string  $jobId;
    public string  $status;       // pending | processing | done | failed
    public string  $reportClass;
    public array   $params;
    public string  $outputFormat; // xlsx | csv
    public int     $totalRows;
    public int     $processedRows;
    public ?string $outputPath;
    public ?string $errorMessage;
    public \DateTimeImmutable $createdAt;
    public \DateTimeImmutable $updatedAt;
}
```

### 2. `ChunkedDataProviderInterface`

```
src/Report/DataProvider/ChunkedDataProviderInterface.php
```

Extends existing providers to expose pagination primitives:

```php
interface ChunkedDataProviderInterface
{
    public function setOffset(int $offset): void;
    public function setLimit(int $limit): void;
    public function getTotalCount(): int;   // for progress tracking
}
```

`AbstractDataProvider` may optionally implement this; concrete providers override `generateData()` to honour `$this->offset` / `$this->limit`.

### 3. `PartialResultStoreInterface`

```
src/AsyncReport/Storage/PartialResultStoreInterface.php
```

```php
interface PartialResultStoreInterface
{
    public function initJob(string $jobId): void;
    public function appendRows(string $jobId, iterable $rows): void;
    public function finalizeJob(string $jobId): string; // returns final file path
    public function getJobState(string $jobId): ReportJobState;
    public function updateJobState(string $jobId, ReportJobState $state): void;
    public function deleteJob(string $jobId): void;
}
```

Concrete implementations:
- `FileSystemPartialResultStore` – writes each chunk to a temporary CSV/serialised file; merges on finalisation.
- `RedisPartialResultStore` – appends serialised rows to a Redis list; finalises when all chunks are stored.

### 4. `ReportJob`

```
src/AsyncReport/ReportJob.php
```

Wraps a concrete `AbstractReport` + its parameters into a serializable unit:

```php
class ReportJob
{
    public function __construct(
        private string $reportClass,
        private array  $params,
        private string $format = 'xlsx',
        private int    $chunkSize = 1000,
    ) {}

    public function getReport(): AbstractReport { ... }
    public function getChunkSize(): int         { ... }
    public function getFormat(): string         { ... }
}
```

### 5. `ReportJobRunner`

```
src/AsyncReport/ReportJobRunner.php
```

Processes a single chunk. Designed to be called from a queue worker, a CLI command, or a plain HTTP request:

```php
class ReportJobRunner
{
    public function __construct(
        private PartialResultStoreInterface $store,
    ) {}

    /**
     * Process one chunk starting at $offset.
     * Returns true when all chunks have been processed (job done).
     */
    public function runChunk(ReportJobState $state): bool
    {
        $report = $this->buildReport($state);          // boot AbstractReport
        $provider = $report->getDataProvider();

        // If the provider supports chunking, paginate
        if ($provider instanceof ChunkedDataProviderInterface) {
            $provider->setOffset($state->processedRows);
            $provider->setLimit($state->chunkSize);
        }

        $rows = iterator_to_array($provider->getData());
        $this->store->appendRows($state->jobId, $rows);

        $state->processedRows += count($rows);
        $isDone = ($state->processedRows >= $state->totalRows)
               || (count($rows) < $state->chunkSize);

        if ($isDone) {
            $state->outputPath = $this->store->finalizeJob($state->jobId);
            $state->status = 'done';
        }

        $this->store->updateJobState($state->jobId, $state);
        return $isDone;
    }
}
```

### 6. `ReportJobManager`

```
src/AsyncReport/ReportJobManager.php
```

High-level API used from controllers / services:

```php
class ReportJobManager
{
    public function schedule(ReportJob $job): string;       // returns jobId
    public function getStatus(string $jobId): ReportJobState;
    public function runNextChunk(string $jobId): ReportJobState;
    public function download(string $jobId): Response;      // Symfony StreamedResponse
    public function cleanup(string $jobId): void;
}
```

### 7. `ContinuationToken` (Strategy B only)

```
src/AsyncReport/ContinuationToken.php
```

```php
class ContinuationToken
{
    public static function encode(ReportJobState $state): string;
    public static function decode(string $token): ReportJobState;
}
```

Encoded as a signed, URL-safe JSON+base64 string (HMAC with `APP_KEY` to prevent tampering).

### 8. `ChunkedSpreadsheetWriter`

```
src/Report/Writer/Spreadsheets/ChunkedSpreadsheetWriter.php
```

A specialised writer that:
1. Opens (or creates) a temporary native-format file on disk
2. Appends rows using PhpSpreadsheet's **chunk writer** mode OR writes rows directly to a temporary CSV and converts to XLSX only on finalisation
3. Is called once per chunk, not once per full report

```php
class ChunkedSpreadsheetWriter extends AbstractSpreadsheet
{
    private string $tempPath;

    public function openForChunking(string $jobId): void { ... }
    public function appendChunk(iterable $rows, Header $header): void { ... }
    public function finalize(): string { ... } // returns final file path
}
```

> **Implementation note**: PhpSpreadsheet does not support appending to an existing xlsx file without loading the full document. The recommended workaround is to accumulate rows into a temporary **CSV or serialised PHP file** per chunk, then do a single final conversion to XLSX/CSV when all chunks are done. This keeps peak memory proportional to *one chunk* plus the final file write, not the entire dataset.

---

## Changes to Existing Classes

| Class | Change | Reason |
|---|---|---|
| `AbstractDataProvider` | Add optional `$offset`, `$limit` properties; add `getTotalCount(): int` default returning 0 | Support chunking without breaking existing providers |
| `AbstractReport` | Add `runChunk(int $offset, int $limit): Generator` that skips `ready` guard and re-runs `generateData()` with chunk params | Needed by `ReportJobRunner` |
| `ReportInterface` | Add optional `supportsChunkedGeneration(): bool` | Allows the runner to fall back to full-load mode gracefully |
| `HasParamsTrait` | No change needed | Params already serialisable to array |
| `AbstractSpreadsheet` | Extract `generateWorksheetData()` to accept an explicit `iterable` instead of calling `$this->report->getData()` | Enables feeding pre-loaded chunks |

---

## Sequence Diagram – Strategy A

```
Client           Controller         ReportJobManager      Queue Worker
  │                  │                     │                   │
  │  POST /export    │                     │                   │
  │─────────────────►│                     │                   │
  │                  │  schedule(job)      │                   │
  │                  │────────────────────►│                   │
  │                  │◄────────────────────│                   │
  │                  │  { jobId: "xyz" }   │                   │
  │◄─────────────────│                     │                   │
  │  { jobId: "xyz" }│                     │   dispatch(job)   │
  │                  │                     │──────────────────►│
  │                  │                     │                   │ runChunk(0)
  │  GET /status/xyz │                     │                   │ runChunk(1000)
  │─────────────────►│                     │                   │ runChunk(2000)
  │◄─────────────────│  { status: "processing", progress: 40% }│
  │                  │                     │                   │ finalize()
  │  GET /status/xyz │                     │                   │
  │─────────────────►│                     │                   │
  │◄─────────────────│  { status: "done" } │                   │
  │                  │                     │                   │
  │  GET /download/xyz                     │                   │
  │─────────────────►│                     │                   │
  │◄─────────────────│  [streamed xlsx]    │                   │
```

---

## Sequence Diagram – Strategy B

```
Client                      Controller
  │  POST /reports/start         │
  │─────────────────────────────►│
  │                              │ ReportJobManager::initJob()
  │                              │ ReportJobRunner::runChunk(offset=0)
  │◄─────────────────────────────│
  │  { token, processed: 1000 }  │
  │                              │
  │  POST /reports/continue      │
  │─────────────────────────────►│
  │                              │ ReportJobRunner::runChunk(offset=1000)
  │◄─────────────────────────────│
  │  { token, processed: 2000 }  │
  │         ...                  │
  │  POST /reports/continue      │
  │─────────────────────────────►│
  │                              │ ReportJobRunner::runChunk (last chunk)
  │                              │ PartialResultStore::finalizeJob()
  │◄─────────────────────────────│
  │  { token, status: "done" }   │
  │                              │
  │  GET /reports/download/{tok} │
  │─────────────────────────────►│ StreamedResponse from file
  │◄─────────────────────────────│
```

---

## File Layout

```
src/
└── AsyncReport/
    ├── ReportJob.php
    ├── ReportJobState.php
    ├── ReportJobManager.php
    ├── ReportJobRunner.php
    ├── ContinuationToken.php
    └── Storage/
        ├── PartialResultStoreInterface.php
        ├── FileSystemPartialResultStore.php
        └── RedisPartialResultStore.php

src/Report/
├── DataProvider/
│   └── ChunkedDataProviderInterface.php   (new)
└── Writer/
    └── Spreadsheets/
        └── ChunkedSpreadsheetWriter.php   (new)
```

---

## Implementation Checklist (for future agent sessions)

- [ ] Create `ChunkedDataProviderInterface` with `setOffset / setLimit / getTotalCount`
- [ ] Add optional chunk support to `AbstractDataProvider` (default no-op / full scan)
- [ ] Create `ReportJobState` DTO
- [ ] Create `PartialResultStoreInterface`
- [ ] Implement `FileSystemPartialResultStore` (append to temp CSV, finalize to XLSX)
- [ ] Create `ReportJob` value object
- [ ] Create `ReportJobRunner` (processes one chunk, updates state)
- [ ] Create `ReportJobManager` (schedule, status, download, cleanup)
- [ ] Implement `ContinuationToken` with HMAC signing
- [ ] Create `ChunkedSpreadsheetWriter` (accept iterable rows, append, finalize)
- [ ] Modify `AbstractReport::run()` to expose `runChunk(offset, limit)` variant
- [ ] Add `supportsChunkedGeneration(): bool` to `ReportInterface`
- [ ] Write unit tests for each new component
- [ ] Write integration test covering a full 3-chunk report round-trip
- [ ] Update `docs/index.md` with usage examples for both strategies

---

## Design Constraints & Notes

1. **No new required dependencies** – the feature should work without a queue library. Strategy A hooks into *any* queue/bus via a simple `interface DispatchableJobInterface`; the library ships a `SynchronousJobDispatcher` as a fallback (runs the job inline, useful for tests or environments with high time limits).

2. **Backward compatibility** – all existing `AbstractReport`, `AbstractDataProvider`, and `AbstractWriter` APIs remain unchanged. Chunked generation is opt-in via `ChunkedDataProviderInterface` and `ReportJobRunner`.

3. **Temp file lifetime** – `PartialResultStoreInterface::deleteJob()` should be called explicitly (e.g., after download) or via a time-based cleanup CLI command. A `cleanOlderThan(\DateInterval $age)` method is recommended on concrete stores.

4. **Security** – `ContinuationToken` must be HMAC-signed to prevent users from forging offset/jobId values. The signing key should come from an injected secret, never hardcoded.

5. **PhpSpreadsheet memory ceiling** – even a single chunk of 1 000 rows with 50 columns stays well within a 128 MB limit. The chunk size should be configurable per report (default: 500–1 000 rows) and documented.

6. **Perspectives & Chapters** – the `ReportJobRunner` must re-instantiate the report with the same `perspective` and pass the same `chapters` collection via params on each chunk request, exactly as `HasParamsTrait::generateParamsForDataProvider()` currently does.

<?php

declare(strict_types=1);

namespace ByTIC\ReportGenerator\Bundle\Admin\Controllers;

use ByTIC\ReportGenerator\AsyncReport\ContinuationToken;
use ByTIC\ReportGenerator\AsyncReport\ReportJobRunner;
use ByTIC\ReportGenerator\AsyncReport\ReportJobState;
use ByTIC\ReportGenerator\AsyncReport\Storage\FileSystemPartialResultStore;
use ByTIC\ReportGenerator\AsyncReport\Storage\PartialResultStoreInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Controller mixin for Strategy B chunked report generation.
 *
 * Mix this trait into any controller that needs to generate large reports
 * across multiple HTTP requests with a live progress bar.
 *
 * ## Flow
 *
 * 1. User navigates to the `reportExport` action → HTML progress-bar page
 *    is returned.  The page initialises a continuation token and stores it
 *    in the JavaScript.
 *
 * 2. The JS polls `reportProgress` (POST with `_report_token`).  Each call
 *    processes one chunk and returns JSON `{done, token, processed, total,
 *    percent}`.
 *
 * 3. When `done === true` the JS redirects to `reportDownload` with the
 *    final token.  The server streams the generated file and then removes
 *    the temporary directory.
 *
 * ## How to use
 *
 * ```php
 * class MyReportController extends \Nip\Controllers\Controller
 * {
 *     use HasReportGeneratorControllerTrait;
 *
 *     protected function getChunkedReportClass(): string
 *     {
 *         return MyReport::class;
 *     }
 *
 *     protected function getChunkedReportParams(): array
 *     {
 *         return ['year' => (int) $this->getRequest()->get('year')];
 *     }
 *
 *     protected function getChunkedReportTokenSecret(): string
 *     {
 *         return $_ENV['APP_KEY'] ?? getenv('APP_KEY');
 *     }
 *
 *     protected function getChunkedReportProgressUrl(): string
 *     {
 *         return '/admin/my-reports/reportProgress';
 *     }
 *
 *     protected function getChunkedReportDownloadUrl(): string
 *     {
 *         return '/admin/my-reports/reportDownload';
 *     }
 * }
 * ```
 *
 * @method Request getRequest()
 */
trait HasReportGeneratorControllerTrait
{
    // =========================================================================
    // Public controller actions
    // =========================================================================

    /**
     * Action: render the progress-bar page.
     *
     * Initialises a new job, encodes the initial continuation token, and
     * returns an HTML page whose JavaScript will drive the chunk-by-chunk
     * processing.
     */
    public function reportExport(): void
    {
        $state = ReportJobState::initial(
            $this->getChunkedReportClass(),
            $this->getChunkedReportParams(),
            $this->getChunkedReportFormat(),
            $this->getChunkedReportChunkSize(),
        );

        $token = ContinuationToken::encode($state, $this->getChunkedReportTokenSecret());

        $html = $this->renderChunkedReportProgressPage(
            $token,
            $this->getChunkedReportProgressUrl(),
            $this->getChunkedReportDownloadUrl(),
            $state,
        );

        $response = new Response($html, Response::HTTP_OK, [
            'Content-Type' => 'text/html; charset=UTF-8',
        ]);
        $response->send();
        exit();
    }

    /**
     * Action: process one chunk (AJAX/JSON endpoint).
     *
     * Expects a POST parameter `_report_token` containing the current
     * continuation token.  Returns JSON:
     *
     * ```json
     * {
     *   "done": false,
     *   "token": "<new-token>",
     *   "processed": 500,
     *   "total": 5000,
     *   "percent": 10
     * }
     * ```
     *
     * When `done` is `true` the caller should redirect to the download action.
     */
    public function reportProgress(): void
    {
        $rawToken = (string) ($this->getRequest()->get('_report_token') ?? '');

        if ($rawToken === '') {
            $this->sendChunkedReportJson(['error' => 'Missing _report_token parameter.'], Response::HTTP_BAD_REQUEST);
            return;
        }

        try {
            $state = ContinuationToken::decode($rawToken, $this->getChunkedReportTokenSecret());
        } catch (\InvalidArgumentException $e) {
            $this->sendChunkedReportJson(['error' => 'Invalid token: ' . $e->getMessage()], Response::HTTP_BAD_REQUEST);
            return;
        }

        // Already finished in a previous round-trip
        if ($state->isDone()) {
            $this->sendChunkedReportJson([
                'done' => true,
                'token' => $rawToken,
                'processed' => $state->processedRows,
                'total' => $state->totalRows,
                'percent' => 100,
            ]);
            return;
        }

        try {
            $runner = $this->getChunkedReportRunner();
            $state = $runner->runChunk($state);
        } catch (\Throwable $e) {
            $this->sendChunkedReportJson(['error' => $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
            return;
        }

        if ($state->isFailed()) {
            $this->sendChunkedReportJson(
                ['error' => $state->errorMessage ?? 'An unknown error occurred.'],
                Response::HTTP_INTERNAL_SERVER_ERROR,
            );
            return;
        }

        $newToken = ContinuationToken::encode($state, $this->getChunkedReportTokenSecret());

        $this->sendChunkedReportJson([
            'done' => $state->isDone(),
            'token' => $newToken,
            'processed' => $state->processedRows,
            'total' => $state->totalRows,
            'percent' => $state->progressPercent(),
        ]);
    }

    /**
     * Action: stream the completed report file to the browser.
     *
     * Expects a query/post parameter `_report_token` containing the
     * continuation token from the final `reportProgress` response.
     *
     * After the response has been sent the temporary chunk files are removed.
     */
    public function reportDownload(): void
    {
        $rawToken = (string) ($this->getRequest()->get('_report_token') ?? '');

        if ($rawToken === '') {
            (new Response('Missing _report_token parameter.', Response::HTTP_BAD_REQUEST))->send();
            exit();
        }

        try {
            $state = ContinuationToken::decode($rawToken, $this->getChunkedReportTokenSecret());
        } catch (\InvalidArgumentException $e) {
            (new Response('Invalid download token.', Response::HTTP_BAD_REQUEST))->send();
            exit();
        }

        if (!$state->isDone() || $state->outputPath === null) {
            (new Response('Report is not ready yet.', Response::HTTP_BAD_REQUEST))->send();
            exit();
        }

        if (!file_exists($state->outputPath)) {
            (new Response('Report file not found.', Response::HTTP_NOT_FOUND))->send();
            exit();
        }

        $runner = $this->getChunkedReportRunner();
        $response = $runner->createDownloadResponse($state);

        $store = $this->getChunkedReportStore();

        // Clean up temporary chunk files after the response has been sent.
        register_shutdown_function(static function () use ($store, $state): void {
            $store->cleanup($state->jobId);
            if ($state->outputPath !== null && file_exists($state->outputPath)) {
                @unlink($state->outputPath);
            }
        });

        $response->send();
        exit();
    }

    // =========================================================================
    // Methods to override in the consuming controller
    // =========================================================================

    /**
     * Return the fully-qualified class name of the report to generate.
     *
     * This method MUST be implemented by the consuming controller.
     */
    protected function getChunkedReportClass(): string
    {
        throw new \RuntimeException(
            'Implement ' . static::class . '::getChunkedReportClass() and return the FQCN of the report.'
        );
    }

    /**
     * Return the parameters to pass to the report constructor.
     *
     * Must only contain JSON-serialisable primitive values (strings, ints,
     * floats, booleans) — no objects or resource handles.
     *
     * @return array<string, mixed>
     */
    protected function getChunkedReportParams(): array
    {
        return [];
    }

    /**
     * Return the output format: `'xlsx'` (default) or `'csv'`.
     */
    protected function getChunkedReportFormat(): string
    {
        return 'xlsx';
    }

    /**
     * Return the number of rows to process per HTTP request.
     */
    protected function getChunkedReportChunkSize(): int
    {
        return 500;
    }

    /**
     * Return the HMAC secret used to sign continuation tokens.
     *
     * This method MUST be implemented by the consuming controller.
     * Use an application-level secret (e.g. `$_ENV['APP_KEY']`).
     */
    protected function getChunkedReportTokenSecret(): string
    {
        throw new \RuntimeException(
            'Implement ' . static::class . '::getChunkedReportTokenSecret() and return the signing secret.'
        );
    }

    /**
     * Return the URL of the `reportProgress` AJAX endpoint.
     *
     * This method MUST be implemented by the consuming controller.
     */
    protected function getChunkedReportProgressUrl(): string
    {
        throw new \RuntimeException(
            'Implement ' . static::class . '::getChunkedReportProgressUrl().'
        );
    }

    /**
     * Return the URL of the `reportDownload` endpoint.
     *
     * This method MUST be implemented by the consuming controller.
     */
    protected function getChunkedReportDownloadUrl(): string
    {
        throw new \RuntimeException(
            'Implement ' . static::class . '::getChunkedReportDownloadUrl().'
        );
    }

    // =========================================================================
    // Internal helpers (may be overridden for customisation)
    // =========================================================================

    /**
     * Return the partial-result store used to persist chunk data.
     *
     * Override to use a custom storage backend (e.g. Redis).
     */
    protected function getChunkedReportStore(): PartialResultStoreInterface
    {
        return new FileSystemPartialResultStore();
    }

    /**
     * Return a {@see ReportJobRunner} wired with the current store.
     */
    protected function getChunkedReportRunner(): ReportJobRunner
    {
        return new ReportJobRunner($this->getChunkedReportStore());
    }

    /**
     * Send a JSON response and terminate the current script.
     *
     * @param array<string, mixed> $data
     */
    protected function sendChunkedReportJson(array $data, int $statusCode = Response::HTTP_OK): void
    {
        $response = new JsonResponse($data, $statusCode);
        $response->send();
        exit();
    }

    /**
     * Render the self-contained HTML progress-bar page.
     *
     * Override to use a custom template / view engine.
     */
    protected function renderChunkedReportProgressPage(
        string $token,
        string $progressUrl,
        string $downloadUrl,
        ReportJobState $state,
    ): string {
        $totalRows = $state->totalRows > 0 ? $state->totalRows : null;

        ob_start();
        require __DIR__ . '/../../../../resources/views/admin/report-generator/export.php';
        return (string) ob_get_clean();
    }
}

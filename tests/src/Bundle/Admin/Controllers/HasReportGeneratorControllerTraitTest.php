<?php

declare(strict_types=1);

namespace ByTIC\ReportGenerator\Tests\Bundle\Admin\Controllers;

use ByTIC\ReportGenerator\AsyncReport\ContinuationToken;
use ByTIC\ReportGenerator\AsyncReport\ReportJobState;
use ByTIC\ReportGenerator\Bundle\Admin\Controllers\HasReportGeneratorControllerTrait;
use PHPUnit\Framework\TestCase;

class HasReportGeneratorControllerTraitTest extends TestCase
{
    public function test_urls_are_compiled_without_context_by_default(): void
    {
        $controller = $this->createController();

        self::assertSame('/reports/reportProgress', $controller->progressUrl());
        self::assertSame('/reports/reportDownload', $controller->downloadUrl());
    }

    public function test_both_urls_preserve_routing_context(): void
    {
        $controller = $this->createController(['id_event' => 42]);

        self::assertSame('/reports/reportProgress?id_event=42', $controller->progressUrl());
        self::assertSame('/reports/reportDownload?id_event=42', $controller->downloadUrl());
    }

    public function test_multiple_exports_have_independent_state_and_shared_endpoints(): void
    {
        $controller = $this->createController(['id_event' => 42]);
        $orders = $this->captureReport($controller, 'App\\Reports\\OrdersReport', ['year' => 2026], 'xlsx');
        $customers = $this->captureReport($controller, 'App\\Reports\\CustomersReport', ['active' => true], 'csv', 200);

        self::assertSame('App\\Reports\\OrdersReport', $orders->reportClass);
        self::assertSame(['year' => 2026], $orders->params);
        self::assertSame('xlsx', $orders->format);
        self::assertSame(500, $orders->chunkSize);
        self::assertSame('App\\Reports\\CustomersReport', $customers->reportClass);
        self::assertSame(['active' => true], $customers->params);
        self::assertSame('csv', $customers->format);
        self::assertSame(200, $customers->chunkSize);
        self::assertNotSame($orders->jobId, $customers->jobId);
        self::assertSame('/reports/reportProgress?id_event=42', $controller->rendered['progressUrl']);
        self::assertSame('/reports/reportDownload?id_event=42', $controller->rendered['downloadUrl']);
    }

    public function test_start_uses_default_parameters_format_and_chunk_size(): void
    {
        $state = $this->captureReport($this->createController(), 'App\\Reports\\OrdersReport');

        self::assertSame([], $state->params);
        self::assertSame('xlsx', $state->format);
        self::assertSame(500, $state->chunkSize);
    }

    public function test_start_uses_overridden_defaults_unless_explicitly_provided(): void
    {
        $controller = $this->createController();
        $controller->defaultFormat = 'csv';
        $controller->defaultChunkSize = 75;

        $defaultState = $this->captureReport($controller, 'App\\Reports\\OrdersReport');
        self::assertSame('csv', $defaultState->format);
        self::assertSame(75, $defaultState->chunkSize);

        $explicitState = $this->captureReport($controller, 'App\\Reports\\OrdersReport', [], 'xlsx', 200);
        self::assertSame('xlsx', $explicitState->format);
        self::assertSame(200, $explicitState->chunkSize);
    }

    public function test_legacy_export_action_preserves_controller_hooks(): void
    {
        $controller = $this->createController();

        try {
            $controller->reportExport();
            self::fail('Expected progress page rendering.');
        } catch (\LogicException $exception) {
            self::assertSame('Captured progress page.', $exception->getMessage());
        }

        $state = ContinuationToken::decode($controller->rendered['token'], 'test-secret');
        self::assertSame('App\\Reports\\LegacyReport', $state->reportClass);
        self::assertSame(['legacy' => true], $state->params);
        self::assertSame('xlsx', $state->format);
        self::assertSame(500, $state->chunkSize);
    }

    private function captureReport(
        object $controller,
        string $reportClass,
        array $params = [],
        ?string $format = null,
        ?int $chunkSize = null,
    ): ReportJobState {
        try {
            $controller->startReport(
                reportClass: $reportClass,
                params: $params,
                format: $format,
                chunkSize: $chunkSize,
            );
            self::fail('Expected progress page rendering.');
        } catch (\LogicException $exception) {
            self::assertSame('Captured progress page.', $exception->getMessage());
        }

        $state = ContinuationToken::decode($controller->rendered['token'], 'test-secret');
        self::assertSame($controller->rendered['state']->toArray(), $state->toArray());

        return $state;
    }

    private function createController(?array $params = null): object
    {
        return new class($params) {
            use HasReportGeneratorControllerTrait {
                getChunkedReportProgressUrl as public progressUrl;
                getChunkedReportDownloadUrl as public downloadUrl;
                getChunkedReportUrlParams as private defaultUrlParams;
                getChunkedReportFormat as private defaultFormat;
                getChunkedReportChunkSize as private defaultChunkSize;
                startChunkedReport as public startReport;
            }

            private ?array $params;
            public array $rendered = [];
            public ?string $defaultFormat = null;
            public ?int $defaultChunkSize = null;

            public function __construct(?array $params)
            {
                $this->params = $params;
            }

            protected function getChunkedReportUrlParams(): array
            {
                return $this->params ?? $this->defaultUrlParams();
            }

            protected function getChunkedReportClass(): string
            {
                return 'App\\Reports\\LegacyReport';
            }

            protected function getChunkedReportParams(): array
            {
                return ['legacy' => true];
            }

            protected function getChunkedReportFormat(): string
            {
                return $this->defaultFormat ?? $this->defaultFormat();
            }

            protected function getChunkedReportChunkSize(): int
            {
                return $this->defaultChunkSize ?? $this->defaultChunkSize();
            }

            protected function getChunkedReportTokenSecret(): string
            {
                return 'test-secret';
            }

            protected function renderChunkedReportProgressPage(
                string $token,
                string $progressUrl,
                string $downloadUrl,
                ReportJobState $state,
            ): string {
                $this->rendered = compact('token', 'progressUrl', 'downloadUrl', 'state');

                throw new \LogicException('Captured progress page.');
            }

            public function getModelManager(): object
            {
                return new class {
                    public function compileURL(string $action, array $params): string
                    {
                        $query = http_build_query($params);

                        return '/reports/' . $action . ($query === '' ? '' : '?' . $query);
                    }
                };
            }
        };
    }
}

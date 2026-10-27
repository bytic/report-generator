<?php

declare(strict_types=1);

namespace ByTIC\ReportGenerator\Tests\Bundle\Admin\Controllers;

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

    private function createController(?array $params = null): object
    {
        return new class($params) {
            use HasReportGeneratorControllerTrait {
                getChunkedReportProgressUrl as public progressUrl;
                getChunkedReportDownloadUrl as public downloadUrl;
                getChunkedReportUrlParams as private defaultUrlParams;
            }

            private ?array $params;

            public function __construct(?array $params)
            {
                $this->params = $params;
            }

            protected function getChunkedReportUrlParams(): array
            {
                return $this->params ?? $this->defaultUrlParams();
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

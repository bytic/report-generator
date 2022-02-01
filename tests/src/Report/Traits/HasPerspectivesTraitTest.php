<?php

declare(strict_types=1);

namespace ByTIC\ReportGenerator\Tests\Report\Traits;

use ByTIC\ReportGenerator\Perspectives\Perspective;
use ByTIC\ReportGenerator\Tests\AbstractTest;
use ByTIC\ReportGenerator\Tests\Fixtures\BasicReport\Report;

/**
 * Class HasPerspectivesTraitTest.
 */
class HasPerspectivesTraitTest extends AbstractTest
{
    public function testCurrentPerspectiveFromConfig()
    {
        $report = new Report();
        $report->createPerspective('test');

        self::assertNull($report->currentPerspective());
        $report->setParams(['perspective' => 'test']);

        $perspective = $report->currentPerspective();
        self::assertInstanceOf(Perspective::class, $perspective);
        self::assertEquals('test', $perspective);
    }
}

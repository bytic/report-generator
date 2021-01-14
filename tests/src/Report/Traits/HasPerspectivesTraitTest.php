<?php

namespace ByTIC\ReportGenerator\Tests\Report\Traits;

use ByTIC\ReportGenerator\Perspectives\Perspective;
use ByTIC\ReportGenerator\Tests\AbstractTest;
use ByTIC\ReportGenerator\Tests\Fixtures\BasicReport\Report;

/**
 * Class HasPerspectivesTraitTest
 * @package ByTIC\ReportGenerator\Tests\Report\Traits
 */
class HasPerspectivesTraitTest extends AbstractTest
{
    public function test_current_perspective_from_config()
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
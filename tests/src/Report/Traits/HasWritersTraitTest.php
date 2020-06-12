<?php

namespace ByTIC\ReportGenerator\Tests\Report\Traits;

use ByTIC\ReportGenerator\Tests\AbstractTest;
use ByTIC\ReportGenerator\Tests\Fixtures\BasicReport\Report;
use ByTIC\ReportGenerator\Tests\Fixtures\BasicReport\XlsxWriter;

/**
 * Class HasWritersTraitTest
 * @package ByTIC\ReportGenerator\Tests\Report\Traits
 */
class HasWritersTraitTest extends AbstractTest
{
    public function test_registerCustomWriters()
    {
        $report = new Report();
        self::assertInstanceOf(XlsxWriter::class, $report->getWriter('Xlsx'));
    }
}

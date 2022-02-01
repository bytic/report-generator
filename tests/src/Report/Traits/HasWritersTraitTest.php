<?php

declare(strict_types=1);

namespace ByTIC\ReportGenerator\Tests\Report\Traits;

use ByTIC\ReportGenerator\Tests\AbstractTest;
use ByTIC\ReportGenerator\Tests\Fixtures\BasicReport\Report;
use ByTIC\ReportGenerator\Tests\Fixtures\BasicReport\XlsxWriter;

/**
 * Class HasWritersTraitTest.
 */
class HasWritersTraitTest extends AbstractTest
{
    public function testRegisterCustomWriters()
    {
        $report = new Report();
        self::assertInstanceOf(XlsxWriter::class, $report->getWriter('Xlsx'));
    }
}

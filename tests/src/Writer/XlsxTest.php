<?php

declare(strict_types=1);

namespace ByTIC\ReportGenerator\Tests\Writer;

use ByTIC\ReportGenerator\Report\Writer\Xlsx;
use ByTIC\ReportGenerator\Tests\AbstractTest;
use ByTIC\ReportGenerator\Tests\Fixtures\BasicReport\Report;

/**
 * Class XlsxTest.
 */
class XlsxTest extends AbstractTest
{
    public function testRender()
    {
        $report = new Report();
        $writer = new Xlsx($report);

        $fixtureFile = TEST_FIXTURE_PATH . '/files/xlsx-simple.xlsx';
        $writer->save($fixtureFile);

        self::assertFileExists($fixtureFile);
        unlink($fixtureFile);
    }
}

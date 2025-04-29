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
        $report->run();

        $writer = new Xlsx($report);

        $fixtureFile = TEST_FIXTURE_PATH . '/files/xlsx-simple.xlsx';
        $writer->save($fixtureFile);

        self::assertFileExists($fixtureFile);
        unlink($fixtureFile);
    }

    public function test_long_sheet_name()
    {
        $report = new Report();
        $reportDefinition = $report->getDefinition();

        $title = str_repeat('a', 100);
        $reportDefinition->getHeader('long_sheet_name', true)
            ->addColumnFromArray(['name' => 'long_sheet_name', 'label' => 'Long Sheet Name ' . $title]);

        $chapter = $report->getDefinition()
            ->getOrCreateChapter('long_sheet_name', 'Long Sheet Name ' . $title);
        $report->run();

        $writer = new Xlsx($report);

        $fixtureFile = TEST_FIXTURE_PATH . '/files/xlsx-test.xlsx';
        $writer->save($fixtureFile);

        self::assertFileExists($fixtureFile);
    }
}

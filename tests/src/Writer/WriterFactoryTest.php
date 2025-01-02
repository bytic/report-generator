<?php

declare(strict_types=1);

namespace ByTIC\ReportGenerator\Tests\Writer;

use ByTIC\ReportGenerator\Report\Writer\Html;
use ByTIC\ReportGenerator\Report\Writer\Spreadsheets\AbstractSpreadsheet;
use ByTIC\ReportGenerator\Report\Writer\WriterFactory;
use ByTIC\ReportGenerator\Tests\AbstractTest;
use ByTIC\ReportGenerator\Tests\Fixtures\BasicReport\Report;

/**
 * Class WriterFactoryTest.
 */
class WriterFactoryTest extends AbstractTest
{
    /**
     * @dataProvider dataCreateWriter
     *
     * @param string $type
     * @param string $writerClass
     */
    public static function testCreateWriter($type, $writerClass)
    {
        $report = new Report();
        $object = WriterFactory::createWriter($report, $type);
        self::assertInstanceOf($writerClass, $object);
    }

    /**
     * @return array
     */
    public static function dataCreateWriter()
    {
        return [
            ['xlsx', AbstractSpreadsheet::class],
            ['html', Html::class],
        ];
    }
}

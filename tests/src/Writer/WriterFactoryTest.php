<?php

namespace ByTIC\ReportGenerator\Tests\Writer;

use ByTIC\ReportGenerator\Report\Writer\Html;
use ByTIC\ReportGenerator\Report\Writer\WriterFactory;
use ByTIC\ReportGenerator\Report\Writer\Xlsx;
use ByTIC\ReportGenerator\Tests\AbstractTest;
use ByTIC\ReportGenerator\Tests\Fixtures\BasicReport\Report;

/**
 * Class WriterFactoryTest
 * @package ByTIC\ReportGenerator\Tests\Writer
 */
class WriterFactoryTest extends AbstractTest
{
    /**
     * @dataProvider dataCreateWriter
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
            ['xlsx', Xlsx::class],
            ['html', Html::class],
        ];
    }
}

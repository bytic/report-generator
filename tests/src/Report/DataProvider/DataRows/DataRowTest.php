<?php

declare(strict_types=1);

namespace ByTIC\ReportGenerator\Tests\Report\DataProvider\DataRows;

use ByTIC\ReportGenerator\Report\DataProvider\DataRows\DataRow;
use ByTIC\ReportGenerator\Report\Definition\Columns\Column;
use ByTIC\ReportGenerator\Tests\AbstractTest;

/**
 * Class DataRowTest.
 */
class DataRowTest extends AbstractTest
{
    public function testGetValueString()
    {
        $data = new DataRow(['year' => 2018]);
        self::assertSame(2018, $data->getValue('year'));
    }

    public function testGetValueNull()
    {
        $data = new DataRow(['year' => 2018]);
        self::assertSame(null, $data->getValue('not_found'));
    }

    public function testGetValueNullWithDefault()
    {
        $data = new DataRow(['year' => 2018]);
        self::assertSame('--', $data->getValue('not_found', '--'));
    }

    public function testGetValueColumn()
    {
        $data = new DataRow(['year' => 2018]);
        $column = new Column(['name' => 'year']);
        self::assertSame(2018, $data->getValue($column));
    }
}

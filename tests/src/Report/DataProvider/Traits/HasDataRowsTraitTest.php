<?php

declare(strict_types=1);

namespace ByTIC\ReportGenerator\Tests\Report\DataProvider\Traits;

use ByTIC\ReportGenerator\Report\DataProvider\DataRows\DataRow;
use ByTIC\ReportGenerator\Tests\AbstractTest;
use ByTIC\ReportGenerator\Tests\Fixtures\BasicReport\DataProvider;
use Generator;

/**
 * Class HasDataRowsTraitTest.
 */
class HasDataRowsTraitTest extends AbstractTest
{
    public function testGenerateDataWithDataRows()
    {
        $dataProvider = new DataProvider();
        $data = $dataProvider->getData();
        $firstRow = $data->current();

        self::assertInstanceOf(Generator::class, $data);
        self::assertCount(2, $data);

        self::assertInstanceOf(DataRow::class, $firstRow);
        self::assertSame(2016, $firstRow->getValue('year'));
    }
}

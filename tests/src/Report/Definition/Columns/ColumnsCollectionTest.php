<?php

declare(strict_types=1);

namespace ByTIC\ReportGenerator\Tests\Report\Definition\Columns;

use ByTIC\ReportGenerator\Report\Definition\Columns\Column;
use ByTIC\ReportGenerator\Report\Definition\Columns\ColumnsCollection;
use ByTIC\ReportGenerator\Tests\AbstractTest;

/**
 * Class ColumnsCollectionTest.
 */
class ColumnsCollectionTest extends AbstractTest
{
    public function testIterator()
    {
        $columnsCollection = new ColumnsCollection();
        $columnsCollection->addColumnSimple('col1');
        $columnsCollection->addColumnSimple('col2');

        $test = '';
        foreach ($columnsCollection as $column) {
            self::assertInstanceOf(Column::class, $column);
            $test .= $column->getName();
        }

        self::assertSame('col1col2', $test);
    }

    public function testArrayAccess()
    {
        $columnsCollection = new ColumnsCollection();
        $columnsCollection->addColumnSimple('col1');
        $columnsCollection->addColumnSimple('col2');

        $columnsCollection[] = new Column(['name' => 'col3']);

        self::assertCount(3, $columnsCollection);
        self::assertSame('col2', $columnsCollection['col2']->getName());

        unset($columnsCollection['col2']);
        self::assertCount(2, $columnsCollection);

        self::assertTrue(isset($columnsCollection['col1']));
        self::assertFalse(isset($columnsCollection['col2']));
    }
}

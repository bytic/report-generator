<?php

declare(strict_types=1);

namespace ByTIC\ReportGenerator\Tests\Report\Definition\Header\Traits;

use ByTIC\ReportGenerator\Report\Definition\Columns\MultiColumn;
use ByTIC\ReportGenerator\Report\Definition\Header\Header;
use ByTIC\ReportGenerator\Tests\AbstractTest;

/**
 * Class HasColumnsTraitTest.
 */
class HasColumnsTraitTest extends AbstractTest
{
    public function testMultiColumnAdd()
    {
        $header = new Header();

        self::assertSame(0, $header->rowsCount());
        $header->addColumnFromArray(['name' => 'parent0']);

        $multiColumn = new MultiColumn(['name' => 'parent1']);
        $multiColumn->addChildFromArray(['name' => 'child11']);
        $multiColumn->addChildFromArray(['name' => 'child12']);

        $multiColumn2 = new MultiColumn(['name' => 'parent2']);
        $multiColumn2->addChildFromArray(['name' => 'child21']);
        $multiColumn2->addChildFromArray(['name' => 'child22']);

        $multiColumn->addChild($multiColumn2);
        $header->addColumn($multiColumn);

        $firstRow = $header->getRow();
        self::assertSame(2, $firstRow->columnsCount());
        self::assertSame(
            ['parent0', 'parent1'],
            $firstRow->getColumnsNames()
        );

        $secondRow = $header->getRow(1);
        self::assertSame(4, $secondRow->columnsCount());
        self::assertSame(
            ['parent0', 'parent1.child11', 'parent1.child12', 'parent1.parent2'],
            $secondRow->getColumnsNames()
        );

        $thirdRow = $header->getRow(2);
        self::assertSame(5, $thirdRow->columnsCount());
        self::assertSame(
            [
                'parent0',
                'parent1.child11',
                'parent1.child12',
                'parent1.parent2.child21',
                'parent1.parent2.child22',
            ],
            $thirdRow->getColumnsNames()
        );
    }
}

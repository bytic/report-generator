<?php

namespace ByTIC\ReportGenerator\Tests\Report\Definition\Columns;

use ByTIC\ReportGenerator\Report\Definition\Columns\MultiColumn;
use ByTIC\ReportGenerator\Tests\AbstractTest;

/**
 * Class MultiColumnTest
 * @package ByTIC\ReportGenerator\Tests\Report\Definition\Columns
 */
class MultiColumnTest extends AbstractTest
{
    public function testConstruct()
    {
        $column = new MultiColumn([
            'name' => 'col',
            'title' => 'Col',
            'isHidden' => true,
        ]);
        self::assertSame(0, $column->getDescendantsCount());

        foreach ([1, 2] as $row1) {
            $child = new MultiColumn([
                'name' => 'child' . $row1,
                'title' => 'child' . $row1
            ]);

            foreach ([1, 2] as $row2) {
                $child->addChildFromArray([
                    'name' => 'child' . $row1 . $row2,
                    'title' => 'child' . $row1 . $row2
                ]);
            }
            $column->addChild($child);
        }
//        $column->addChildFromArray([
//            'name' => 'child' . $row1 . $row2,
//            'title' => 'child' . $row1 . $row2
//        ]);
        self::assertSame(4, $column->getDescendantsCount());
    }
}

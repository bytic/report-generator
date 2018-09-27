<?php

namespace ByTIC\ReportGenerator\Tests\Report\Definition\Columns;

use ByTIC\ReportGenerator\Report\Definition\Columns\Column;
use ByTIC\ReportGenerator\Tests\AbstractTest;

/**
 * Class ColumnTest
 * @package ByTIC\ReportGenerator\Tests\Report\Definition\Columns
 */
class ColumnTest extends AbstractTest
{
    public function testConstruct()
    {
        $params = [
            'name' => 'col',
            'title' => 'Col',
            'isHidden' => true,
        ];
        $column = new Column($params);

        static::assertSame($params['name'], $column->getName());
        static::assertSame($params['title'], $column->getTitle());
        static::assertSame($params['isHidden'], $column->isHidden());
    }
}

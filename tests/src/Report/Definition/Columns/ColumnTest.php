<?php

declare(strict_types=1);

namespace ByTIC\ReportGenerator\Tests\Report\Definition\Columns;

use ByTIC\ReportGenerator\Report\Definition\Columns\Column;
use ByTIC\ReportGenerator\Tests\AbstractTest;

/**
 * Class ColumnTest.
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

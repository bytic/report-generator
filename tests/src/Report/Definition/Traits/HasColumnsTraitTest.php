<?php

namespace ByTIC\ReportGenerator\Tests\Report\Definition\Traits;

use ByTIC\ReportGenerator\Report\Definition\Columns\Column;
use ByTIC\ReportGenerator\Report\Definition\Definition;
use ByTIC\ReportGenerator\Tests\AbstractTest;

/**
 * Class HasColumnsTraitTest
 * @package ByTIC\ReportGenerator\Tests\Report\Definition\Traits
 */
class HasColumnsTraitTest extends AbstractTest
{
    public function testAddColumnSimple()
    {
        $definition = new Definition();
        $definition->addColumnSimple('name');
        $column = $definition->getColumn('name');

        static::assertInstanceOf(Column::class, $column);
        static::assertSame('name', $column->getName());
        static::assertSame('name', $column->getTitle());
    }
}

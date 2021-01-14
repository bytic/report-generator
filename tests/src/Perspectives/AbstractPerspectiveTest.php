<?php

namespace ByTIC\ReportGenerator\Tests\Perspectives;

use ByTIC\ReportGenerator\Perspectives\Perspective;
use ByTIC\ReportGenerator\Tests\AbstractTest;

/**
 * Class AbstractPerspectiveTest
 * @package ByTIC\ReportGenerator\Tests\Perspectives
 */
class AbstractPerspectiveTest extends AbstractTest
{
    public function test_cast_to_string()
    {
        $perspective = new Perspective();
        $perspective->setName('test');

        self::assertEquals('test', $perspective);
        self::assertTrue('test' == $perspective);
    }
}
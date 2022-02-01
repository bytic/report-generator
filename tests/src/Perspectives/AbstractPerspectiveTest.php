<?php

declare(strict_types=1);

namespace ByTIC\ReportGenerator\Tests\Perspectives;

use ByTIC\ReportGenerator\Perspectives\Perspective;
use ByTIC\ReportGenerator\Tests\AbstractTest;

/**
 * Class AbstractPerspectiveTest.
 */
class AbstractPerspectiveTest extends AbstractTest
{
    public function testCastToString()
    {
        $perspective = new Perspective();
        $perspective->setName('test');

        self::assertEquals('test', $perspective);
        self::assertTrue('test' == $perspective);
    }
}

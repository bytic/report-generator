<?php

declare(strict_types=1);

namespace ByTIC\ReportGenerator\Tests\Perspectives;

use ByTIC\ReportGenerator\Perspectives\Perspective;
use ByTIC\ReportGenerator\Perspectives\PerspectiveCollection;
use ByTIC\ReportGenerator\Tests\AbstractTest;
use Nip\Collections\Exceptions\InvalidTypeException;
use stdClass;

/**
 * Class PerspectiveCollectionTest.
 */
class PerspectiveCollectionTest extends AbstractTest
{
    public function testAcceptsOnlyPerspectives()
    {
        $collection = new PerspectiveCollection();
        static::expectException(InvalidTypeException::class);
        $collection->add(new stdClass());
    }

    public function testKeyByName()
    {
        $perspective = new Perspective();
        $perspective->setName('test');

        $collection = new PerspectiveCollection();
        $collection->add($perspective);

        self::assertTrue($collection->has('test'));
        self::assertSame($collection->get('test'), $perspective);
    }
}

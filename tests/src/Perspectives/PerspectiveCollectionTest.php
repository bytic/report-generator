<?php

namespace ByTIC\ReportGenerator\Tests\Perspectives;

use ByTIC\ReportGenerator\Perspectives\Perspective;
use ByTIC\ReportGenerator\Perspectives\PerspectiveCollection;
use ByTIC\ReportGenerator\Tests\AbstractTest;

/**
 * Class PerspectiveCollectionTest
 * @package ByTIC\ReportGenerator\Tests\Perspectives
 */
class PerspectiveCollectionTest extends AbstractTest
{
    public function test_accepts_only_perspectives()
    {
        $collection = new PerspectiveCollection();
        static::expectException(\Nip\Collections\Exceptions\InvalidTypeException::class);
        $collection->add(new \stdClass());
    }

    public function test_key_by_name()
    {
        $perspective = new Perspective();
        $perspective->setName('test');

        $collection = new PerspectiveCollection();
        $collection->add($perspective);

        self::assertTrue($collection->has('test'));
        self::assertSame($collection->get('test'), $perspective);
    }
}
<?php

namespace ByTIC\ReportGenerator\Tests\Report\Traits;

use ByTIC\ReportGenerator\Fixtures\BasicReport\Report;
use ByTIC\ReportGenerator\Tests\AbstractTest;

/**
 * Class HasDataProviderTraitTest
 * @package ByTIC\ReportGenerator\Tests\Report\Traits
 */
class HasDataProviderTraitTest extends AbstractTest
{
    public function testInitParams()
    {
        $params = ['p1' => 1, 'p2' =>2];
        $report = new Report();
        $report->setParams($params);

        $dataProvider = $report->getDataProvider();
        static::assertSame($params, $dataProvider->getParams());
    }
}

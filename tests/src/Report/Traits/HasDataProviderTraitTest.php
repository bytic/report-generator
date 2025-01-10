<?php

declare(strict_types=1);

namespace ByTIC\ReportGenerator\Tests\Report\Traits;

use ByTIC\ReportGenerator\Tests\AbstractTest;
use ByTIC\ReportGenerator\Tests\Fixtures\BasicReport\Report;

/**
 * Class HasDataProviderTraitTest.
 */
class HasDataProviderTraitTest extends AbstractTest
{
    public function testInitParams()
    {
        $params = ['p1' => 1, 'p2' => 2];
        $report = new Report();
        $report->setParams($params);

        $dataProvider = $report->getDataProvider();
        $dataProviderParams = $dataProvider->getParams();
        foreach ($params as $key => $value) {
            self::assertSame($value, $dataProviderParams[$key]);
        }
    }
}

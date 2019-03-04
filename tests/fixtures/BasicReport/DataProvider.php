<?php

namespace ByTIC\ReportGenerator\Tests\Fixtures\BasicReport;

use ByTIC\ReportGenerator\Report\DataProvider\AbstractDataProvider;
use Generator;

/**
 * Class DataProvider
 * @package ByTIC\ReportGenerator\Tests\Fixtures\BasicReport
 */
class DataProvider extends AbstractDataProvider
{

    /**
     * @return Generator
     */
    protected function generateData()
    {
        $this->yieldDataRow(['year' => 2016, 'amount' => 320]);
    }
}

<?php

declare(strict_types=1);

namespace ByTIC\ReportGenerator\Tests\Fixtures\BasicReport;

use ByTIC\ReportGenerator\Report\DataProvider\AbstractDataProvider;
use Generator;

/**
 * Class DataProvider.
 */
class DataProvider extends AbstractDataProvider
{
    /**
     * @return Generator
     */
    protected function generateData()
    {
        yield $this->newDataRow(['year' => 2016, 'amount' => 320]);
        yield $this->newDataRow(['year' => 2018, 'amount' => 350]);
    }
}

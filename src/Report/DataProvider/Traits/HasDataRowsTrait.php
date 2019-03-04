<?php

namespace ByTIC\ReportGenerator\Report\DataProvider\Traits;

use ByTIC\ReportGenerator\Report\DataProvider\DataRows\DataRow;

/**
 * Trait HasDataRowsTrait
 * @package ByTIC\ReportGenerator\Report\DataProvider\Traits
 */
trait HasDataRowsTrait
{
    /**
     * @param array $data
     * @return \Generator
     */
    protected function yieldDataRow($data = [])
    {
        yield $this->newDataRow($data);
    }

    /**
     * @param array $data
     * @return DataRow
     */
    protected function newDataRow($data = [])
    {
        return new DataRow($data);
    }
}

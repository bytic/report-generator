<?php

declare(strict_types=1);

namespace ByTIC\ReportGenerator\Report\DataProvider\Traits;

use ByTIC\ReportGenerator\Report\DataProvider\DataRows\DataRow;
use Generator;

/**
 * Trait HasDataRowsTrait.
 */
trait HasDataRowsTrait
{
    /**
     * @param array $data
     *
     * @return Generator
     */
    protected function yieldDataRow($data = [])
    {
        yield $data instanceof DataRow ? $data : $this->newDataRow($data);
    }

    /**
     * @param array $data
     *
     * @return DataRow
     */
    protected function newDataRow($data = [])
    {
        return new DataRow($data);
    }
}

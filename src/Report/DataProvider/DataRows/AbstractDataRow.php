<?php

namespace ByTIC\ReportGenerator\Report\DataProvider\DataRows;

use ByTIC\ReportGenerator\Report\Definition\Columns\Column;

/**
 * Class AbstractDataRow
 * @package ByTIC\ReportGenerator\Report\DataProvider\DataRows
 */
abstract class AbstractDataRow
{
    protected $data = [];

    /**
     * @param $column
     * @param null $default
     * @return mixed|null
     */
    public function getValue($column, $default = null)
    {
        $column = $column instanceof Column ? $column->getName() : $column;
        if (!isset($this->data[$column])) {
            return $default;
        }
        return $this->data[$column];
    }

    /**
     * AbstractDataRow constructor.
     * @param array $data
     */
    public function __construct(array $data)
    {
        $this->data = $data;
    }
}

<?php

namespace ByTIC\ReportGenerator\Report\Definition\Header\Traits;

use ByTIC\ReportGenerator\Report\Definition\Columns\ColumnsCollection;

/**
 * Trait HasLevelsTrait
 * @package ByTIC\ReportGenerator\Report\Definition\Header
 */
trait HasRowsTrait
{
    /**
     * @var ColumnsCollection[]
     */
    protected $rows = [];

    /**
     * @return int
     */
    public function rowsCount()
    {
        return count($this->rows);
    }

    /**
     * @param int $rowNum
     * @return ColumnsCollection
     */
    public function getRow($rowNum = null)
    {
        $rowNum = $rowNum === null ? 0 : $rowNum;
        if (!isset($this->rows[$rowNum])) {
            $this->initRow($rowNum);
        }

        return $this->rows[$rowNum];
    }

    /**
     * @param $i
     */
    protected function initRow($i)
    {
        $this->rows[$i] = new ColumnsCollection();
        if (isset($this->rows[$i -1])) {
            $this->rows[$i]->populateFromSibling($this->rows[$i -1]);
        }
    }
}
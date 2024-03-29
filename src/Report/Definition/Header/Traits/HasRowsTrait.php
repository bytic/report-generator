<?php

declare(strict_types=1);

namespace ByTIC\ReportGenerator\Report\Definition\Header\Traits;

use ByTIC\ReportGenerator\Report\Definition\Columns\ColumnsCollection;

/**
 * Trait HasLevelsTrait.
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
     *
     * @return ColumnsCollection
     */
    public function getRow($rowNum = null)
    {
        $rowNum = null === $rowNum ? 0 : $rowNum;
        if (!isset($this->rows[$rowNum])) {
            $this->initRow($rowNum);
        }

        return $this->rows[$rowNum];
    }

    /**
     * @return ColumnsCollection[]
     */
    public function getRows(): array
    {
        return $this->rows;
    }

    /**
     * @return ColumnsCollection|mixed
     */
    public function getLastRow()
    {
        return end($this->rows);
    }

    /**
     * @param $i
     */
    protected function initRow($i)
    {
        $this->rows[$i] = new ColumnsCollection();
        if (isset($this->rows[$i - 1])) {
            $this->rows[$i]->populateFromSibling($this->rows[$i - 1]);
        }
        ksort($this->rows);
    }
}

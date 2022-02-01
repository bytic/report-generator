<?php

declare(strict_types=1);

namespace ByTIC\ReportGenerator\Report\Definition\Header\Traits;

use ByTIC\ReportGenerator\Report\Definition\Columns\Column;
use ByTIC\ReportGenerator\Report\Definition\Columns\ColumnsCollection;
use ByTIC\ReportGenerator\Report\Definition\Columns\MultiColumn;

/**
 * Trait HasColumnsTrait.
 */
trait HasColumnsTrait
{
    /**
     * Set the column definitions.
     *
     * @param Column[] $columns
     * @param null $row
     *
     * @return $this
     */
    public function setColumns(array $columns = [], $row = null)
    {
        return $this->getRow($row)->setColumns($columns);
    }

    /**
     * @param null $row
     *
     * @return ColumnsCollection
     */
    public function getColumns($row = null)
    {
        return $this->getRow($row);
    }

    /**
     * Add a column.
     *
     * @param string $name
     * @param string|null $title
     *
     * @return $this
     */
    public function addColumnSimple($name, $title = null, $row = null)
    {
        return $this->getRow($row)->addColumnSimple($name, $title);
    }

    /**
     * Add a column.
     *
     * @param $array
     *
     * @return $this
     */
    public function addColumnFromArray($array, $row = null)
    {
        return $this->getRow($row)->addColumnFromArray($array);
    }

    /**
     * Add a column.
     *
     * @return $this
     */
    public function addColumn(Column $column, $row = null)
    {
        if ($column instanceof MultiColumn) {
            return $this->addMultiColumn($column, $row);
        }

        return $this->getRow($row)->addColumn($column);
    }

    /**
     * @param Column $column
     * @param null $row
     *
     * @return
     */
    protected function addMultiColumn(MultiColumn $column, $row = null)
    {
        $currentRow = null === $row ? 0 : $row;

        $children = $column->getChildren();
        foreach ($children as $child) {
            $this->addColumn($child, $currentRow + 1);
        }

        return $this->getRow($row)->addColumn($column);
    }

    /**
     * Get a column by name.
     *
     * @param string $name
     *
     * @return Column|null
     */
    public function getColumn($name, $row = null)
    {
        return $this->getRow($row)->getColumn($name);
    }
}

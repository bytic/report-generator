<?php

namespace ByTIC\ReportGenerator\Report\Definition\Traits;

use ByTIC\ReportGenerator\Report\Definition\Columns\Column;

/**
 * Trait HasColumnsTrait
 * @package ByTIC\ReportGenerator\Report\Definition\Traits
 */
trait HasColumnsTrait
{
    /**
     * Column definitions.
     *
     * @var Column[]
     */
    protected $columns = [];

    /**
     * Set the column definitions.
     *
     * @param Column[] $columns
     *
     * @return $this
     */
    public function setColumns(array $columns = [])
    {
        $this->columns = [];
        foreach ($columns as $column) {
            $this->addColumn($column);
        }
        return $this;
    }

    /**
     * @return Column[]
     */
    public function getColumns()
    {
        return $this->columns;
    }

    /**
     * Add a column.
     *
     * @param string $name
     * @param string|null $title
     * @return $this
     */
    public function addColumnSimple($name, $title = null)
    {
        return $this->addColumnFromArray(['name' => $name, 'title' => $title]);
    }

    /**
     * Add a column.
     *
     * @param $array
     * @return $this
     */
    public function addColumnFromArray($array)
    {
        return $this->addColumn(new Column($array));
    }

    /**
     * Add a column.
     *
     * @param Column $column
     *
     * @return $this
     */
    public function addColumn(Column $column)
    {
        $this->columns[$column->getName()] = $column;
        return $this;
    }

    /**
     * Get a column by name.
     *
     * @param string $name
     *
     * @return Column|null
     */
    public function getColumn($name)
    {
        if (isset($this->columns[$name])) {
            return $this->columns[$name];
        }
        return null;
    }
}

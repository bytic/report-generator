<?php

namespace ByTIC\ReportGenerator\Report\Definition\Columns;

use ArrayAccess;
use ArrayIterator;
use IteratorAggregate;

/**
 * Class ColumnsCollection
 * @package ByTIC\ReportGenerator\Report\Definition\Columns
 */
class ColumnsCollection implements IteratorAggregate, ArrayAccess
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

    /**
     * @return array
     */
    public function getColumnsNames()
    {
        return array_keys($this->columns);
    }

    /**
     * @return int
     */
    public function columnsCount()
    {
        return count($this->columns);
    }

    /**
     * @param ColumnsCollection $collection
     */
    public function populateFromSibling(ColumnsCollection $collection)
    {
        $this->setColumns($collection->getColumns());
    }

    /**
     * @return ArrayIterator
     */
    public function getIterator()
    {
        return new ArrayIterator($this->columns);
    }

    /**
     * @inheritDoc
     */
    public function offsetExists($offset)
    {
        return isset($this->columns[$offset]) || array_key_exists($offset, $this->columns);
    }

    /**
     * @inheritDoc
     */
    public function offsetGet($offset)
    {
        return array_key_exists($offset, $this->columns) ? $this->columns[$offset] : null;
    }

    /**
     * @inheritDoc
     */
    public function offsetSet($offset, $value)
    {
        $this->columns[$offset] = $value;
    }

    /**
     * @inheritDoc
     */
    public function offsetUnset($offset)
    {
        if (!isset($this->items[$offset]) && !array_key_exists($offset, $this->columns)) {
            return null;
        }
        $removed = $this->columns[$offset];
        unset($this->columns[$offset]);

        return $removed;
    }
}

<?php

declare(strict_types=1);

namespace ByTIC\ReportGenerator\Report\Definition\Columns;

use Nip\Collections\Typed\ClassCollection;

/**
 * Class ColumnsCollection.
 */
class ColumnsCollection extends ClassCollection
{
    protected $validClass = Column::class;

    /**
     * Set the column definitions.
     *
     * @param Column[] $columns
     *
     * @return $this
     */
    public function setColumns(array $columns = [])
    {
        $this->clear();
        foreach ($columns as $column) {
            $this->addColumn($column);
        }

        return $this;
    }

    /**
     * @return Column[]
     */
    public function getColumns(): array
    {
        return $this->items;
    }

    /**
     * Add a column.
     *
     * @param string $name
     * @param string|null $title
     *
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
     *
     * @return $this
     */
    public function addColumnFromArray($array)
    {
        return $this->addColumn(new Column($array));
    }

    /**
     * Add a column.
     *
     * @return $this
     */
    public function addColumn(Column $column): static
    {
        $this->add($column, $column->getName());

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
        return $this->get($name);
    }

    /**
     * @return array
     */
    public function getColumnsNames(): array
    {
        return $this->keys();
    }

    /**
     * @return int
     */
    public function columnsCount(): int
    {
        return $this->count();
    }

    public function populateFromSibling(ColumnsCollection $collection)
    {
        $this->setColumns($collection->getColumns());
    }
}

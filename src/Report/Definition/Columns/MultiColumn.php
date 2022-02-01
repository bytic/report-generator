<?php

declare(strict_types=1);

namespace ByTIC\ReportGenerator\Report\Definition\Columns;

use ByTIC\ReportGenerator\Report\Definition\Columns\MultiColumn\DescendentsCalculation;

/**
 * Class Column.
 */
class MultiColumn extends Column
{
    /**
     * @var Column[]
     */
    protected $children = [];

    protected $descendantsCount = 0;

    /**
     * @return Column[]
     */
    public function getChildren(): array
    {
        return $this->children;
    }

    /**
     * @return int|void
     */
    public function childrenCount()
    {
        return count($this->children);
    }

    /**
     * Add a column.
     *
     * @param $array
     *
     * @return $this
     */
    public function addChildFromArray($array)
    {
        return $this->addChild(new Column($array));
    }

    /**
     * @param Column $column
     */
    public function addChild($column)
    {
        if ($column instanceof MultiColumn) {
            $column->prependChildrenNames($this->getName());
        }
        $column->setParam('parentName', $this->getName());
        $column->prependName($this->getName());
        $this->children[$column->getName()] = $column;

        $this->calculateDescendantsCount();
    }

    public function getDescendantsCount(): int
    {
        return $this->descendantsCount;
    }

    public function setDescendantsCount(int $descendantsCount)
    {
        $this->descendantsCount = $descendantsCount;
    }

    /**
     * @param null $children
     *
     * @return int|void
     */
    public function calculateDescendantsCount()
    {
        $this->setDescendantsCount(DescendentsCalculation::calculate($this));
    }

    /**
     * @param Column $column
     *
     * @return bool
     */
    public function isFirstChild($column)
    {
        $keys = array_keys($this->children);
        $firstKey = reset($keys);

        return $column->getName() == $firstKey;
    }

    /**
     * @param $prepend
     */
    public function prependChildrenNames($prepend)
    {
        foreach ($this->children as $child) {
            $child->prependName($prepend);
        }
    }
}

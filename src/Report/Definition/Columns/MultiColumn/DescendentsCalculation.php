<?php

namespace ByTIC\ReportGenerator\Report\Definition\Columns\MultiColumn;

use ByTIC\ReportGenerator\Report\Definition\Columns\MultiColumn;

class DescendentsCalculation
{
    /**
     * @var MultiColumn
     */
    protected $column;

    protected $levels = [];

    /**
     * DescendentsCalculation constructor.
     * @param $column
     */
    public function __construct($column)
    {
        $this->column = $column;
    }

    /**
     * @param MultiColumn $column
     * @return int
     */
    public static function calculate($column)
    {
        $calculator = new self($column);
        return $calculator->run();
    }

    /**
     * @return mixed
     */
    protected function run()
    {
        $this->scan();
        ksort($this->levels);
        return array_pop($this->levels);
    }

    /**
     * @param null $children
     * @param int $level
     */
    protected function scan($children = null, $level = 0)
    {
        $children = $children === null ? $this->column->getChildren() : $children;
        foreach ($children as $child) {
            if ($child instanceof MultiColumn) {
                $this->scan($child->getChildren(), $level +1);
            }
            if (!isset($this->levels[$level])) {
                $this->levels[$level] = 0;
            }
            $this->levels[$level]++;
        }
    }
}

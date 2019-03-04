<?php

namespace ByTIC\ReportGenerator\Report\Definition\Traits;

use ByTIC\ReportGenerator\Report\Definition\Columns\Column;
use ByTIC\ReportGenerator\Report\Definition\Header\Header;

/**
 * Trait HasColumnsTrait
 * @package ByTIC\ReportGenerator\Report\Definition\Traits
 */
trait HasHeaderTrait
{
    /**
     * @var null|Header
     */
    protected $header = null;

    /**
     * @return Header|null
     */
    public function getHeader(): Header
    {
        return $this->header;
    }

    protected function initHeader()
    {
        $this->header = new Header();
    }

    /**
     * Set the column definitions.
     *
     * @param Column[] $columns
     *
     * @param null $row
     * @return $this
     */
    public function setColumns(array $columns = [], $row = null)
    {
        return $this->getHeader()->setColumns($columns, $row);
    }

    /**
     * @param null $row
     * @return ColumnsCollection
     */
    public function getColumns($row = null)
    {
        return $this->getHeader()->getRow($row);
    }

    /**
     * Add a column.
     *
     * @param string $name
     * @param string|null $title
     * @return $this
     */
    public function addColumnSimple($name, $title = null, $row = null)
    {
        return $this->getHeader()->addColumnSimple($name, $title, $row);
    }

    /**
     * Add a column.
     *
     * @param $array
     * @param null $row
     * @return $this
     */
    public function addColumnFromArray($array, $row = null)
    {
        return $this->getHeader()->addColumnFromArray($array, $row);
    }

    /**
     * Add a column.
     *
     * @param Column $column
     *
     * @param null $row
     * @return $this
     */
    public function addColumn(Column $column, $row = null)
    {
        return $this->getHeader()->addColumn($column, $row);
    }

    /**
     * Get a column by name.
     *
     * @param string $name
     *
     * @param null $row
     * @return Column|null
     */
    public function getColumn($name, $row = null)
    {
        return $this->getHeader()->getColumn($name, $row);
    }
}

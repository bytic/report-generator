<?php

declare(strict_types=1);

namespace ByTIC\ReportGenerator\ReportHeaders\Related;

use ByTIC\ReportGenerator\Report\Definition\Columns\Column;
use ByTIC\ReportGenerator\Report\Definition\Columns\ColumnsCollection;
use ByTIC\ReportGenerator\Report\Definition\Header\Header;
use ByTIC\ReportGenerator\ReportHeaders\HeaderCollection;

/**
 * Trait HasColumnsTrait.
 */
trait HasHeadersTrait
{
    /**
     * @var HeaderCollection|null
     */
    protected $headers = null;

    public function getHeaders(): ?HeaderCollection
    {
        if ($this->headers === null) {
            $this->initHeaders();
        }

        return $this->headers;
    }

    /**
     * @param null $key
     * @return Header
     */
    public function getHeader($key = null, $autoInit = false): ?Header
    {
        $key = $this->checkHeaderKey($key);
        $headers = $this->getHeaders();
        if ($autoInit && !$headers->has($key)) {
            $headers->add(new Header(), $key);
        }
        return $this->getHeaders()->get($key);
    }

    /**
     * Set the column definitions.
     *
     * @param Column[] $columns
     * @param null $row
     *
     * @return Header
     */
    public function setColumns(array $columns = [], $row = null)
    {
        return $this->getHeader(null, true)->setColumns($columns, $row);
    }

    /**
     * @param null $row
     *
     * @return ColumnsCollection
     */
    public function getColumns($row = null)
    {
        return $this->getHeader(null, true)->getRow($row);
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
        return $this->getHeader(null, true)->addColumnSimple($name, $title, $row);
    }

    /**
     * Add a column.
     *
     * @param $array
     * @param null $row
     *
     * @return $this
     */
    public function addColumnFromArray($array, $row = null)
    {
        return $this->getHeader(null, true)->addColumnFromArray($array, $row);
    }

    /**
     * Add a column.
     *
     * @param null $row
     *
     * @return $this
     */
    public function addColumn(Column $column, $row = null)
    {
        return $this->getHeader(null, true)->addColumn($column, $row);
    }

    /**
     * Get a column by name.
     *
     * @param string $name
     * @param null $row
     *
     * @return Column|null
     */
    public function getColumn($name, $row = null)
    {
        return $this->getHeader(null, true)->getColumn($name, $row);
    }

    protected function initHeaders()
    {
        $this->headers = new HeaderCollection();
    }

    /**
     * @param $key
     * @return mixed|string
     */
    protected function checkHeaderKey($key = null)
    {
        return $key ?? HeaderCollection::DEFAULT_HEADER;
    }
}

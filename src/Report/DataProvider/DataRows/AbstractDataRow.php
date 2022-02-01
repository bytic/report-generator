<?php

declare(strict_types=1);

namespace ByTIC\ReportGenerator\Report\DataProvider\DataRows;

use ArrayAccess;
use ByTIC\ReportGenerator\Report\Definition\Columns\Column;
use Nip\Collections\Traits\AccessMethodsTrait;
use Nip\Collections\Traits\ArrayAccessTrait;
use Nip\Collections\Traits\OperationsOnItemsTrait;

/**
 * Class AbstractDataRow.
 */
abstract class AbstractDataRow implements ArrayAccess
{
    use ArrayAccessTrait;
    use AccessMethodsTrait;
    use OperationsOnItemsTrait;

    /**
     * @var array
     */
    protected $items = [];
    protected $index = 0;

    /**
     * Collection constructor.
     *
     * @param array $items
     */
    public function __construct($items = [])
    {
        if (is_array($items)) {
            $this->items = $items;
        } elseif (method_exists($items, 'toArray')) {
            $this->items = $items->toArray();
        }
    }

    /**
     * @param $column
     * @param $value
     */
    public function setValue($column, $value)
    {
        $column = $this->keyForColumn($column);
        $this->set($column, $value);
    }

    /**
     * @param $column
     * @param null $default
     *
     * @return mixed|null
     */
    public function getValue($column, $default = null)
    {
        $column = $this->keyForColumn($column);

        return $this->get($column, $default);
    }

    /** @noinspection PhpDocMissingThrowsInspection
     * @param $column
     * @param $value
     */
    public function addValue($column, $value)
    {
        $column = $this->keyForColumn($column);
        /* @noinspection PhpUnhandledExceptionInspection */
        $this->valueAdd($column, $value);
    }

    /**
     * @param $column
     *
     * @return string
     */
    protected function keyForColumn($column)
    {
        return $column instanceof Column ? $column->getName() : $column;
    }
}

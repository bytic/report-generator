<?php

declare(strict_types=1);

namespace ByTIC\ReportGenerator\Report\Definition\Header\Traits;

use ArrayIterator;

/**
 * Trait ArrayMethodsTrait.
 */
trait ArrayMethodsTrait
{
    /**
     * @return ArrayIterator
     */
    public function getIterator()
    {
        return new ArrayIterator($this->rows);
    }

    /**
     * {@inheritDoc}
     */
    public function offsetExists($offset)
    {
        return isset($this->rows[$offset]) || array_key_exists($offset, $this->rows);
    }

    /**
     * {@inheritDoc}
     */
    public function offsetGet($offset)
    {
        return array_key_exists($offset, $this->rows) ? $this->rows[$offset] : null;
    }

    /**
     * {@inheritDoc}
     */
    public function offsetSet($offset, $value)
    {
        $this->rows[$offset] = $value;
    }

    /**
     * {@inheritDoc}
     */
    public function offsetUnset($offset)
    {
        if (!isset($this->rows[$offset]) && !array_key_exists($offset, $this->rows)) {
            return null;
        }
        $removed = $this->rows[$offset];
        unset($this->rows[$offset]);

        return $removed;
    }
}

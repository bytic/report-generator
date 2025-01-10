<?php

declare(strict_types=1);

namespace ByTIC\ReportGenerator\Report\Definition\Header\Traits;

use ArrayIterator;
use Traversable;

/**
 * Trait ArrayMethodsTrait.
 */
trait ArrayMethodsTrait
{
    /**
     * @return ArrayIterator
     */
    public function getIterator(): Traversable
    {
        return new ArrayIterator($this->rows);
    }

    /**
     * {@inheritDoc}
     */
    public function offsetExists($offset): bool
    {
        return isset($this->rows[$offset]) || array_key_exists($offset, $this->rows);
    }

    /**
     * {@inheritDoc}
     */
    public function offsetGet($offset): mixed
    {
        return array_key_exists($offset, $this->rows) ? $this->rows[$offset] : null;
    }

    /**
     * {@inheritDoc}
     */
    public function offsetSet($offset, $value): void
    {
        $this->rows[$offset] = $value;
    }

    /**
     * {@inheritDoc}
     */
    public function offsetUnset($offset): void
    {
        if (!isset($this->rows[$offset]) && !array_key_exists($offset, $this->rows)) {
            return;
        }
        $removed = $this->rows[$offset];
        unset($this->rows[$offset]);
    }
}

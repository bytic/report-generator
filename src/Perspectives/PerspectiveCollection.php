<?php

declare(strict_types=1);

namespace ByTIC\ReportGenerator\Perspectives;

use Nip\Collections\Typed\ClassCollection;

/**
 * Class PerspectiveCollection.
 */
class PerspectiveCollection extends ClassCollection
{
    protected $validClass = Perspective::class;

    /**
     * {@inheritDoc}
     */
    public function add($element, $key = null)
    {
        if (is_null($key) && $element instanceof $this->validClass) {
            $key = $element->getName();
        }
        parent::add($element, $key);
    }
}

<?php

declare(strict_types=1);

namespace ByTIC\ReportGenerator\ReportChapters;

use Nip\Collections\Typed\ClassCollection;

/**
 * Class PerspectiveCollection.
 */
class ReportChaptersCollection extends ClassCollection
{
    protected $validClass = ReportChapter::class;

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

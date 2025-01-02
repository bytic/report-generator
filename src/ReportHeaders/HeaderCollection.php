<?php

declare(strict_types=1);

namespace ByTIC\ReportGenerator\ReportHeaders;

use ByTIC\ReportGenerator\Report\Definition\Header\Header;
use Nip\Collections\Typed\ClassCollection;

/**
 * Class HeaderCollection.
 */
class HeaderCollection extends ClassCollection
{
    protected $validClass = Header::class;

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

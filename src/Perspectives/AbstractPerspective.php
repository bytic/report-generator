<?php

declare(strict_types=1);

namespace ByTIC\ReportGenerator\Perspectives;

/**
 * Class AbstractPerspective.
 */
abstract class AbstractPerspective
{
    /**
     * @var string
     */
    protected $name;

    /**
     * @var string
     */
    protected $label;

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name)
    {
        $this->name = $name;
    }

    public function getLabel(): string
    {
        return $this->label;
    }

    public function setLabel(string $label)
    {
        $this->label = $label;
    }

    public function __toString(): string
    {
        return $this->getName();
    }
}

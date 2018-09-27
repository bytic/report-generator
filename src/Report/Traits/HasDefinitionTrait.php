<?php

namespace ByTIC\ReportGenerator\Report\Traits;

use ByTIC\ReportGenerator\Report\Definition\AbstractDefinition;
use ByTIC\ReportGenerator\Report\Definition\Definition;

/**
 * Trait HasDefinitionTrait
 * @package ByTIC\ReportGenerator\Report\Traits
 */
trait HasDefinitionTrait
{
    /**
     * The report definition.
     *
     * @var AbstractDefinition
     */
    protected $definition = null;

    /**
     * @return AbstractDefinition
     */
    public function getDefinition()
    {
        if ($this->definition === null) {
            $this->initDefinition();
        }
        return $this->definition;
    }

    /**
     * @param AbstractDefinition $definition
     */
    public function setDefinition($definition)
    {
        $this->definition = $definition;
    }

    protected function initDefinition()
    {
        $class = $this->initDefinitionClass();
        $this->definition = new $class();
    }

    /**
     * @return string
     */
    protected function initDefinitionClass()
    {
        if (method_exists($this, 'getDefinitionClass')) {
            return $this->getDefinitionClass();
        }
        return Definition::class;
    }
}

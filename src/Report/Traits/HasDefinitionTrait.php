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
    protected $defined = false;

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
        /** @var AbstractDefinition $definition */
        $definition = new $class();
        $definition->setParams($this->generateParamsForDefinition());
        $this->definition = $definition;
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

    protected function validateDefinition()
    {
        if ($this->isDefined()) {
            return;
        }
        $this->define();
        $this->setDefined(true);
    }

    /**
     * @param bool $defined
     */
    public function setDefined(bool $defined)
    {
        $this->defined = $defined;
    }

    /**
     * @return bool
     */
    public function isDefined(): bool
    {
        return $this->defined;
    }

    /**
     * Method for setting up the report definition.
     */
    abstract protected function define();
}

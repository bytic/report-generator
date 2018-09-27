<?php

namespace ByTIC\ReportGenerator\Report;

use ByTIC\ReportGenerator\Report\Traits\HasDefinitionTrait;

/**
 * Class ReportAbstract
 * @package ByTIC\ReportGenerator\Report
 */
abstract class AbstractReport
{
    use HasDefinitionTrait;

    public function __construct()
    {
        $this->initDefinition();
        $this->define();
    }

    /**
     * Method for setting up the report definition.
     */
    abstract protected function define();

    protected function render()
    {

    }
}

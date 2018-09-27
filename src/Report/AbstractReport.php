<?php

namespace ByTIC\ReportGenerator\Report;

use ByTIC\ReportGenerator\Report\Traits\HasDefinitionTrait;
use ByTIC\ReportGenerator\Report\Traits\HasWritersTrait;

/**
 * Class ReportAbstract
 * @package ByTIC\ReportGenerator\Report
 */
abstract class AbstractReport
{
    use HasDefinitionTrait;
    use HasWritersTrait;

    public function __construct()
    {
        $this->initDefinition();
        $this->define();
    }

    public function render()
    {
        $this->getWriter()->render();
    }

    /**
     * Method for setting up the report definition.
     */
    abstract protected function define();
}

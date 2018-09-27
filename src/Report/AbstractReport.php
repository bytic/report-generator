<?php

namespace ByTIC\ReportGenerator\Report;

use ByTIC\ReportGenerator\Report\Traits\HasDataProvider;
use ByTIC\ReportGenerator\Report\Traits\HasDefinitionTrait;
use ByTIC\ReportGenerator\Report\Traits\HasWritersTrait;
use ByTIC\ReportGenerator\Utility\Traits\HasParamsTrait;

/**
 * Class ReportAbstract
 * @package ByTIC\ReportGenerator\Report
 */
abstract class AbstractReport
{
    use HasDefinitionTrait;
    use HasWritersTrait;
    use HasDataProvider;
    use HasParamsTrait;

    /**
     * AbstractReport constructor.
     * @param array $params
     */
    public function __construct($params = [])
    {
        $this->setParams($params);
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

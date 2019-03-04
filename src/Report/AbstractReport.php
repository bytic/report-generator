<?php

namespace ByTIC\ReportGenerator\Report;

use ByTIC\ReportGenerator\Report\Definition\Columns\Column;
use ByTIC\ReportGenerator\Report\Traits\HasDataProvider;
use ByTIC\ReportGenerator\Report\Traits\HasDefinitionTrait;
use ByTIC\ReportGenerator\Report\Traits\HasWritersTrait;
use ByTIC\ReportGenerator\Report\Traits\HasParamsTrait;

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
     * @var bool
     */
    protected $ready = false;

    /**
     * AbstractReport constructor.
     * @param array $params
     */
    public function __construct($params = [])
    {
        $this->setParams($params);
    }

    public function run()
    {
        if ($this->isReady()) {
            return;
        }
        $this->validateDefinition();
        $this->generateData();
    }

    public function render()
    {
        $this->getWriter()->render();
    }

    /**
     * Get the resulting column display names after running report.
     *
     * @return Column[]|Definition\Header\Header
     */
    public function getHeader()
    {
        $this->run();
        return $this->getDefinition()->getHeader();
    }

    /**
     * @return bool
     */
    public function isReady(): bool
    {
        return $this->ready;
    }

    /**
     * @param bool $ready
     */
    public function setReady(bool $ready)
    {
        $this->ready = $ready;
    }
}

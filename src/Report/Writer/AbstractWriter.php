<?php

namespace ByTIC\ReportGenerator\Report\Writer;

use ByTIC\ReportGenerator\Report\ReportInterface;
use ByTIC\ReportGenerator\Report\Writer\Traits\CanRenderTrait;

/**
 * Class AbstractWriter
 * @package ByTIC\ReportGenerator\Report\Writer
 */
abstract class AbstractWriter
{
    use CanRenderTrait;

    /**
     * @var ReportInterface
     */
    protected $report;

    /**
     * WriterAbstract constructor.
     *
     * @param ReportInterface $report
     */
    public function __construct(ReportInterface $report)
    {
        $this->report = $report;
    }

    /**
     * Save to file or stream.
     *
     * @param string $name
     */
    abstract public function save($name);

    /**
     * @return ReportInterface
     */
    public function getReport()
    {
        return $this->report;
    }

    /**
     * @param ReportInterface $report
     */
    public function setReport($report)
    {
        $this->report = $report;
    }
}

<?php

namespace ByTIC\ReportGenerator\Report\Writer;

use ByTIC\ReportGenerator\Report\ReportInterface;

/**
 * Class AbstractWriter
 * @package ByTIC\ReportGenerator\Report\Writer
 */
abstract class AbstractWriter
{
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
}

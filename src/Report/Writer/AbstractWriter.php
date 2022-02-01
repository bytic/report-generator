<?php

declare(strict_types=1);

namespace ByTIC\ReportGenerator\Report\Writer;

use ByTIC\ReportGenerator\Report\ReportInterface;
use ByTIC\ReportGenerator\Report\Writer\Traits\CanRenderTrait;

/**
 * Class AbstractWriter.
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

    /**
     * @return string
     */
    protected function getFileName()
    {
        return $this->getReport()->getDefinition()->getFileName() . '.' . $this->getFileExtension();
    }

    /**
     * @return string
     */
    abstract protected function getFileExtension();
}

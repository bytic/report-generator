<?php

namespace ByTIC\ReportGenerator\Report\Definition;

use ByTIC\ReportGenerator\Report\Definition\Traits\HasColumnsTrait;
use ByTIC\ReportGenerator\Utility\Traits\HasParamsTrait;

/**
 * Class AbstractDefinition
 * @package ByTIC\ReportGenerator\Report\Definition
 */
abstract class AbstractDefinition
{
    use HasParamsTrait;
    use HasColumnsTrait;

    /**
     * The report title.
     *
     * @var string
     */
    protected $title;

    /**
     * The file name
     *
     * @var string
     */
    protected $fileName;

    /**
     * @param string $title
     * @return $this
     */
    public function setTitle($title)
    {
        $this->title = $title;
        return $this;
    }

    /**
     * @return string
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * @return string
     */
    public function getFileName()
    {
        return $this->fileName;
    }

    /**
     * @param string $fileName
     */
    public function setFileName($fileName)
    {
        $this->fileName = $fileName;
    }
}

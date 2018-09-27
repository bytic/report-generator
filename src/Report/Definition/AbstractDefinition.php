<?php

namespace ByTIC\ReportGenerator\Report\Definition;

/**
 * Class AbstractDefinition
 * @package ByTIC\ReportGenerator\Report\Definition
 */
abstract class AbstractDefinition
{
    /**
     * The report title.
     *
     * @var string
     */
    protected $title;

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
}

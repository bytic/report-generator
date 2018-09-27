<?php

namespace ByTIC\ReportGenerator\Report\Definition\Columns;

use ByTIC\ReportGenerator\Utility\Traits\HasParamsTrait;

/**
 * Class Column
 * @package ByTIC\ReportGenerator\Report\Definition\Columns
 */
class Column
{
    use HasParamsTrait;

    /**
     * The column name.
     *
     * @var string
     */
    protected $name;

    /**
     * The column name.
     *
     * @var string
     */
    protected $title;

    /**
     * @var boolean
     */
    protected $isHidden = true;

    /**
     * Column constructor.
     * @param array $params
     */
    public function __construct($params = [])
    {
        $this->setParams($params);
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @param string $name
     */
    public function setName(string $name)
    {
        $this->name = $name;
    }

    /**
     * @return string
     */
    public function getTitle(): string
    {
        return $this->title;
    }

    /**
     * @param string $title
     */
    public function setTitle(string $title)
    {
        $this->title = $title;
    }

    /**
     * @return bool
     */
    public function isHidden(): bool
    {
        return $this->isHidden;
    }

    /**
     * @param bool $isHidden
     */
    public function setIsHidden(bool $isHidden)
    {
        $this->isHidden = $isHidden;
    }
}

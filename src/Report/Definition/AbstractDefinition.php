<?php

declare(strict_types=1);

namespace ByTIC\ReportGenerator\Report\Definition;

use ByTIC\ReportGenerator\Report\Definition\Traits\HasHeaderTrait;
use ByTIC\ReportGenerator\Utility\Traits\HasParamsTrait;

/**
 * Class AbstractDefinition.
 */
abstract class AbstractDefinition
{
    use HasParamsTrait;
    use HasHeaderTrait;

    /**
     * The report title.
     *
     * @var string
     */
    protected $title;

    /**
     * The file name.
     *
     * @var string
     */
    protected $fileName;

    /**
     * AbstractDefinition constructor.
     */
    public function __construct()
    {
        $this->initHeader();
    }

    /**
     * @param string $title
     *
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

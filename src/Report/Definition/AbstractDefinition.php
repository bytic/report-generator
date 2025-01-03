<?php

declare(strict_types=1);

namespace ByTIC\ReportGenerator\Report\Definition;

use ByTIC\ReportGenerator\ReportChapters\Related\HasChaptersTrait;
use ByTIC\ReportGenerator\ReportHeaders\Related\HasHeadersTrait;
use ByTIC\ReportGenerator\Utility\Traits\HasParamsTrait;

/**
 * Class AbstractDefinition.
 */
abstract class AbstractDefinition
{
    use HasParamsTrait;
    use HasHeadersTrait;
    use HasChaptersTrait;
    use Traits\HasFilename;

    /**
     * The report title.
     *
     * @var string
     */
    protected $title = null;


    /**
     * AbstractDefinition constructor.
     */
    public function __construct()
    {
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
        return $this->title ?? 'Report';
    }

}

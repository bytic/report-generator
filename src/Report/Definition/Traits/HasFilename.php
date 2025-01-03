<?php

declare(strict_types=1);

namespace ByTIC\ReportGenerator\Report\Definition\Traits;

/**
 *
 */
trait HasFilename
{
    /**
     * The file name.
     *
     * @var string
     */
    protected $fileName = null;

    /**
     * @return string
     */
    public function getFileName()
    {
        return $this->fileName ?? $this->getTitle();
    }

    /**
     * @param string $fileName
     */
    public function setFileName($fileName)
    {
        $this->fileName = $fileName;
    }

}


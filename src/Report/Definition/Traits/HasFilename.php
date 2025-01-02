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
    protected $fileName;

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


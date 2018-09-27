<?php

namespace ByTIC\ReportGenerator\Report\Writer;

use ByTIC\ReportGenerator\Report\ReportInterface;

/**
 * Class WriterFactory
 * @package ByTIC\ReportGenerator\Report\Writer
 */
class WriterFactory
{
    /**
     * @param ReportInterface $report
     * @param string $type
     * @return AbstractWriter
     */
    public static function createWriter(ReportInterface $report, $type)
    {
        $writerClass = static::writerClass($type);
        return new $writerClass($report);
    }

    /**
     * @param $type
     * @return string
     */
    public static function writerClass($type)
    {
        if (strpos($type, '\\')) {
            return $type;
        }
        return static::writerClassBase($type);
    }

    /**
     * @param $type
     * @return string
     */
    public static function writerClassBase($type)
    {
        return 'ByTIC\ReportGenerator\Report\Writer\\' . ucfirst($type);
    }
}

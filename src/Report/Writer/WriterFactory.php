<?php

declare(strict_types=1);

namespace ByTIC\ReportGenerator\Report\Writer;

use ByTIC\ReportGenerator\Report\ReportInterface;
use ByTIC\ReportGenerator\Report\Traits\HasWritersTrait;

/**
 * Class WriterFactory.
 */
class WriterFactory
{
    /**
     * @param ReportInterface|HasWritersTrait $report
     * @param string $type
     *
     * @return AbstractWriter|WriterInterface
     */
    public static function createWriter(ReportInterface $report, $type): WriterInterface
    {
        $writerClass = static::writerClass($type);

        return new $writerClass($report);
    }

    /**
     * @param $type
     */
    public static function writerClass($type): string
    {
        if (strpos($type, '\\')) {
            return $type;
        }

        return static::writerClassBase($type);
    }

    /**
     * @param $type
     */
    public static function writerClassBase($type): string
    {
        $base = 'ByTIC\ReportGenerator\Report\Writer\\';

        if (class_exists($base . ucfirst($type))) {
            return $base . $type;
        }

        return 'ByTIC\ReportGenerator\Report\Writer\\' . ucfirst($type) . 'Writer';
    }
}

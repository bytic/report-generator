<?php

declare(strict_types=1);

namespace ByTIC\ReportGenerator\Report\Writer;

use ByTIC\ReportGenerator\Report\Writer\Spreadsheets\AbstractSpreadsheet;

/**
 * Class Xlsx.
 */
class Xlsx extends AbstractSpreadsheet implements WriterInterface
{
    protected $writerType = 'Xlsx';

    protected function getFileExtension(): string
    {
        return '.xlsx';
    }

    protected function getResponseContentHeader(): string
    {
        return 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet; charset=utf-8';
    }
}

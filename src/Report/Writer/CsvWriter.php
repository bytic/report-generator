<?php

declare(strict_types=1);

namespace ByTIC\ReportGenerator\Report\Writer;

/**
 * Class Html.
 */
class CsvWriter extends AbstractSpreadsheet implements WriterInterface
{
    protected $writerType = 'Csv';

    protected function getResponseContentHeader(): string
    {
        return 'text/csv; charset=utf-8';
    }

    protected function getFileExtension(): string
    {
        return '.csv';
    }
}

<?php

declare(strict_types=1);

namespace ByTIC\ReportGenerator\Tests\Fixtures\BasicReport;

use ByTIC\ReportGenerator\Report\AbstractReport;
use ByTIC\ReportGenerator\Report\ReportInterface;

/**
 * Class Report.
 */
class Report extends AbstractReport implements ReportInterface
{
    /**
     * Method for setting up the report definition.
     */
    protected function define()
    {
        $this->getDefinition()
            ->setTitle('Total Report');

        $this->getDefinition()
            ->addColumnSimple('year')
            ->addColumnSimple('amount');
    }

    /**
     * {@inheritDoc}
     */
    protected function registerCustomWriters()
    {
        return [
            'Xlsx' => XlsxWriter::class,
        ];
    }
}

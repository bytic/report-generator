<?php

namespace ByTIC\ReportGenerator\Tests\Fixtures\BasicReport;

use ByTIC\ReportGenerator\Report\AbstractReport;

/**
 * Class Report
 * @package ByTIC\ReportGenerator\Tests\Fixtures\BasicReport
 */
class Report extends AbstractReport
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
}

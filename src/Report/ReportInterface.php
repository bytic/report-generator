<?php

namespace ByTIC\ReportGenerator\Report;

use ByTIC\ReportGenerator\Report\Definition\AbstractDefinition as Definition;

/**
 * Interface ReportInterface
 * @package ByTIC\ReportGenerator\Report
 */
interface ReportInterface
{
    /**
     * @return Definition
     */
    public function getDefinition();
}

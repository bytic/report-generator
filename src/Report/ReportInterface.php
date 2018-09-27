<?php

namespace ByTIC\ReportGenerator\Report;

use ByTIC\ReportGenerator\Report\Definition\AbstractDefinition as Definition;
use ByTIC\ReportGenerator\Report\Definition\Columns\Column;

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

    /**
     * @return Column[]
     */
    public function getHeader();

    /**
     * @return \Generator
     */
    public function getData();
}

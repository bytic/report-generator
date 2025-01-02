<?php

declare(strict_types=1);

namespace ByTIC\ReportGenerator\Report;

use ByTIC\ReportGenerator\Report\DataProvider\DataRows\DataRow;
use ByTIC\ReportGenerator\Report\Definition\AbstractDefinition as Definition;
use ByTIC\ReportGenerator\Report\Definition\Columns\Column;
use ByTIC\ReportGenerator\Report\Definition\Header\Header;
use Generator;

/**
 * Interface ReportInterface.
 */
interface ReportInterface
{
    /**
     * @return Definition
     */
    public function getDefinition();

    /**
     * @return Column[]|Header
     */
    public function getHeader();

    /**
     * @return Generator|DataRow[]
     */
    public function getData();
}

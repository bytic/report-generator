<?php

namespace ByTIC\ReportGenerator\Report;

use ByTIC\ReportGenerator\Report\Traits\HasDefinitionTrait;

/**
 * Class ReportAbstract
 * @package ByTIC\ReportGenerator\Report
 */
class ReportAbstract
{
    use HasDefinitionTrait;

    /**
     * ReportAbstract constructor.
     *
     * @param array $options
     */
    public function __construct(array $options = [])
    {
        $this->initDefinition();
        $this->define();
    }
}

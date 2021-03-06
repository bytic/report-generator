<?php

namespace ByTIC\ReportGenerator\Report\DataProvider;

use ByTIC\ReportGenerator\Report\DataProvider\Traits\HasDataRowsTrait;
use ByTIC\ReportGenerator\Utility\Traits\HasParamsTrait;
use Generator;

/**
 * Class AbstractDataProvider
 * @package ByTIC\ReportGenerator\Report\DataProvider
 */
abstract class AbstractDataProvider
{
    use HasParamsTrait;
    use HasDataRowsTrait;

    /**
     * @var Generator
     */
    protected $data;

    /**
     * @return Generator
     */
    public function getData()
    {
        if ($this->data === null) {
            $this->initData();
        }
        return $this->data;
    }

    /**
     * @param Generator $data
     */
    public function setData(Generator $data)
    {
        $this->data = $data;
    }

    protected function initData()
    {
        $this->setData($this->generateData());
    }

    /**
     * @return Generator
     */
    abstract protected function generateData();
}

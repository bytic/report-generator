<?php

namespace ByTIC\ReportGenerator\Report\Traits;

use ByTIC\ReportGenerator\Report\DataProvider\AbstractDataProvider;

/**
 * Class HasDataProvider
 * @package ByTIC\ReportGenerator\Report\Traits
 */
trait HasDataProvider
{
    /**
     * The report definition.
     *
     * @var AbstractDataProvider
     */
    protected $dataProvider = null;

    /**
     * @return AbstractDataProvider
     */
    public function getDataProvider()
    {
        return $this->dataProvider;
    }

    /**
     * @param AbstractDataProvider $dataProvider
     */
    public function setDataProvider($dataProvider)
    {
        $this->dataProvider = $dataProvider;
    }
}

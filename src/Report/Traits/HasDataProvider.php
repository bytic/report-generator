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
        if ($this->dataProvider === null) {
            $this->initDataProvider();
        }
        return $this->dataProvider;
    }

    /**
     * @param AbstractDataProvider $dataProvider
     */
    public function setDataProvider($dataProvider)
    {
        $dataProvider->setParams($this->generateParamsForDataProvider());
        $this->dataProvider = $dataProvider;
    }

    protected function initDataProvider()
    {
        $class = $this->initDataProviderClass();
        /** @var AbstractDataProvider $definition */
        $definition = new $class();
        $this->setDataProvider($definition);
    }

    /**
     * @return string
     */
    protected function initDataProviderClass()
    {
        if (method_exists($this, 'getDataProviderClass')) {
            return $this->getDataProviderClass();
        }
        return $this->generateDataProviderClass();
    }

    /**
     * @return string
     */
    protected function generateDataProviderClass()
    {
        try {
            $class = new \ReflectionClass($this);
            return $class->getNamespaceName() . '\\DataProvider';
        } catch (\ReflectionException $exception) {
        }
        return null;
    }
}

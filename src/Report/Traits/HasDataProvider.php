<?php

declare(strict_types=1);

namespace ByTIC\ReportGenerator\Report\Traits;

use ByTIC\ReportGenerator\Report\DataProvider\AbstractDataProvider;
use ByTIC\ReportGenerator\Report\DataProvider\DataRows\DataRow;
use Generator;
use ReflectionClass;
use ReflectionException;

/**
 * Class HasDataProvider.
 */
trait HasDataProvider
{
    protected $data = null;

    /**
     * The report definition.
     *
     * @var AbstractDataProvider
     */
    protected $dataProvider = null;

    /**
     * @return Generator|DataRow[]
     */
    public function getData()
    {
        $this->run();

        return $this->data;
    }

    protected function generateData()
    {
        $this->data = $this->getDataProvider()->getData();
    }

    /**
     * @return AbstractDataProvider
     */
    public function getDataProvider()
    {
        if (null === $this->dataProvider) {
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
            $class = new ReflectionClass($this);

            return $class->getNamespaceName() . '\\DataProvider';
        } catch (ReflectionException $exception) {
        }

        return null;
    }
}

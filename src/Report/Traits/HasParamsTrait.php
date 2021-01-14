<?php

namespace ByTIC\ReportGenerator\Report\Traits;

/**
 * Trait HasParamsTrait
 * @package ByTIC\ReportGenerator\Report\Traits
 */
trait HasParamsTrait
{
    use \ByTIC\ReportGenerator\Utility\Traits\HasParamsTrait;

    /**
     * @return mixed
     */
    protected function generateParamsForDefinition()
    {
        return $this->getParams();
    }

    /**
     * @return mixed
     */
    protected function generateParamsForDataProvider()
    {
        $params = $this->getParams();
        $params['perspective'] = $this->currentPerspective();
        return $params;
    }
}

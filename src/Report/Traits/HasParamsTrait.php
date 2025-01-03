<?php

declare(strict_types=1);

namespace ByTIC\ReportGenerator\Report\Traits;

/**
 * Trait HasParamsTrait.
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
        $params['chapters'] = $this->getDefinition()->getChapters();
        return $params;
    }
}

<?php

namespace ByTIC\ReportGenerator\Utility\Traits;

/**
 * Trait HasParamsTrait
 * @package ByTIC\ReportGenerator\Utility
 */
trait HasParamsTrait
{
    protected $params;

    /**
     * @return mixed
     */
    public function getParams()
    {
        return $this->params;
    }

    /**
     * @param mixed $params
     */
    public function setParams($params)
    {
        foreach ($params as $name => $value) {
            if ($value) {
                $this->setParam($name, $value);
            }
        }
    }

    /**
     * @param $name
     * @param null $default
     * @return null
     */
    public function getParam($name, $default = null)
    {
        $method = $this->generateGetParamMethod($name);
        if (method_exists($this, $method)) {
            return $this->$method($name, $default);
        }
        return isset($this->params[$name]) ? $this->params[$name] : $default;
    }

    /**
     * @param $name
     * @param $value
     */
    public function setParam($name, $value)
    {
        $this->params[$name] = $value;
        $this->setParamMethod($name, $value);
    }

    /**
     * @param $name
     * @param $value
     */
    protected function setParamMethod($name, $value)
    {
        $method = $this->generateSetParamMethod($name);
        if (method_exists($this, $method)) {
            $this->$method($value);
        }
    }

    /**
     * @param $name
     * @return string
     */
    protected function generateGetParamMethod($name)
    {
        return 'get' . ucfirst($name);
    }

    /**
     * @param $name
     * @return string
     */
    protected function generateSetParamMethod($name)
    {
        return 'set' . ucfirst($name);
    }
}

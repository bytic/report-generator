<?php

namespace ByTIC\ReportGenerator\Report\Traits;

use ByTIC\ReportGenerator\Report\Writer\AbstractWriter;
use ByTIC\ReportGenerator\Report\Writer\WriterFactory;

/**
 * Trait HasWritersTrait
 * @package ByTIC\ReportGenerator\Report\Traits
 */
trait HasWritersTrait
{
    protected $type = null;

    /**
     * @var AbstractWriter[]
     */
    protected $writers = [];

    /**
     * @param null $type
     * @return AbstractWriter
     */
    public function getWriter($type = null)
    {
        $type = $this->checkWriterType($type);
        if (!isset($this->writers[$type])) {
            $this->initWriter($type);
        }
        return $this->writers[$type];
    }

    /**
     * @param null $type
     */
    protected function initWriter($type = null)
    {
        $this->type = $this->generateWriter($type);
    }

    /**
     * @param string $type
     * @return AbstractWriter
     */
    protected function generateWriter($type)
    {
        return WriterFactory::createWriter($this, $type);
    }

    /**
     * @param null $type
     * @return null
     */
    public function checkWriterType($type = null)
    {
        if ($this->validWriterType($type)) {
            return $type;
        }
        return $this->getWriterType();
    }

    /**
     * @param string $type
     * @return bool
     */
    protected function validWriterType($type)
    {
        return is_string($type) && strlen($type) != 1;
    }

    /**
     * @return null
     */
    public function getWriterType()
    {
        if ($this->type === null) {
            $this->initType();
        }
        return $this->type;
    }

    protected function initType()
    {
        $this->type = $this->getWriterTypeDefault();
    }

    /**
     * @return string
     */
    public function getWriterTypeDefault()
    {
        return 'xlsx';
    }
}

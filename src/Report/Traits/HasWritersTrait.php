<?php

declare(strict_types=1);

namespace ByTIC\ReportGenerator\Report\Traits;

use ByTIC\ReportGenerator\Report\Writer\AbstractWriter;
use ByTIC\ReportGenerator\Report\Writer\WriterFactory;

/**
 * Trait HasWritersTrait.
 */
trait HasWritersTrait
{
    protected $type = null;

    /**
     * @var AbstractWriter[]
     */
    protected $writers = [];

    /**
     * @var string[]
     */
    protected $customWriters = null;

    /**
     * @param null $type
     *
     * @return AbstractWriter
     */
    public function getWriter($type = null)
    {
        $this->checkRegisterCustomWriters();
        $type = $this->checkWriterType($type);
        if (!isset($this->writers[$type])) {
            $this->initWriter($type);
        }

        return $this->writers[$type];
    }

    /**
     * @return array|string[]|null
     */
    protected function checkRegisterCustomWriters()
    {
        if (null === $this->customWriters) {
            $this->customWriters = $this->registerCustomWriters();
        }

        return $this->customWriters;
    }

    /**
     * @return array
     */
    protected function registerCustomWriters()
    {
        return [];
    }

    /**
     * @param null $type
     */
    protected function initWriter($type = null)
    {
        $type = $this->checkWriterType($type);
        $this->writers[$type] = $this->generateWriter($type);
    }

    /**
     * @param string $type
     *
     * @return AbstractWriter
     */
    protected function generateWriter($type)
    {
        return WriterFactory::createWriter($this, $type);
    }

    /**
     * @param null $type
     *
     * @return null
     */
    public function checkWriterType($type = null)
    {
        if (isset($this->customWriters[$type])) {
            return $this->customWriters[$type];
        }
        if ($this->validWriterType($type)) {
            return $type;
        }

        return $this->getWriterType();
    }

    /**
     * @param string $type
     *
     * @return bool
     */
    protected function validWriterType($type)
    {
        return is_string($type) && 1 != strlen($type);
    }

    /**
     * @return null
     */
    public function getWriterType()
    {
        if (null === $this->type) {
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

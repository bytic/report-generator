<?php

declare(strict_types=1);

namespace ByTIC\ReportGenerator\Report\DataProvider;

use ByTIC\ReportGenerator\Utility\Traits\HasParamsTrait;
use Generator;

/**
 * Class AbstractDataProvider.
 */
abstract class AbstractDataProvider
{
    use HasParamsTrait;
    use Traits\HasDataRowsTrait;
    use Traits\HasChaptersTrait;

    /**
     * @var Generator
     */
    protected $data;

    /**
     * @return Generator
     */
    public function getData()
    {
        if (null === $this->data) {
            $this->initData();
        }

        return $this->data;
    }

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

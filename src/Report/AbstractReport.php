<?php

declare(strict_types=1);

namespace ByTIC\ReportGenerator\Report;

use ByTIC\ReportGenerator\Report\Definition\Columns\Column;

/**
 * Class ReportAbstract.
 */
abstract class AbstractReport
{
    use Traits\HasDataProvider;
    use Traits\HasDefinitionTrait;
    use Traits\HasParamsTrait;
    use Traits\HasPerspectivesTrait;
    use Traits\HasWritersTrait;

    /**
     * @var bool
     */
    protected $ready = false;

    /**
     * AbstractReport constructor.
     *
     * @param array $params
     */
    public function __construct($params = [])
    {
        $this->setParams($params);
    }

    public function run()
    {
        if ($this->isReady()) {
            return;
        }
        $this->validateDefinition();
        $this->generateData();
    }

    /**
     * Pre-load report data from an array of pre-fetched rows.
     *
     * This bypasses the DataProvider and is used by the chunked generation
     * pipeline ({@see \ByTIC\ReportGenerator\AsyncReport\ReportJobRunner})
     * to inject accumulated rows at finalisation time.
     *
     * @param \ByTIC\ReportGenerator\Report\DataProvider\DataRows\DataRow[] $rows
     */
    public function preloadData(array $rows): void
    {
        $this->validateDefinition();
        $this->data = (static function () use ($rows): \Generator {
            yield from $rows;
        })();
        $this->setReady(true);
    }

    public function render()
    {
        $this->getWriter()->render();
    }

    /**
     * Get the resulting column display names after running report.
     *
     * @return Column[]|Definition\Header\Header
     */
    public function getHeader($key = null, $autoInit = false)
    {
        $this->run();

        return $this->getDefinition()->getHeader($key, $autoInit);
    }

    public function isReady(): bool
    {
        return $this->ready;
    }

    public function setReady(bool $ready)
    {
        $this->ready = $ready;
    }
}

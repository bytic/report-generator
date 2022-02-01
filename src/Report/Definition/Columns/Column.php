<?php

declare(strict_types=1);

namespace ByTIC\ReportGenerator\Report\Definition\Columns;

use ByTIC\ReportGenerator\Utility\Traits\HasParamsTrait;

/**
 * Class Column.
 */
class Column
{
    use HasParamsTrait;

    /**
     * The column name.
     *
     * @var string
     */
    protected $name;

    /**
     * The column name.
     *
     * @var string
     */
    protected $title;

    /**
     * @var bool
     */
    protected $isHidden = true;

    /**
     * Column constructor.
     *
     * @param array $params
     */
    public function __construct($params = [])
    {
        $this->setParams($params);
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name)
    {
        $this->name = $name;
    }

    public function prependName(string $name)
    {
        $this->name = $name . '.' . $this->name;
    }

    public function getTitle(): string
    {
        if (empty($this->title)) {
            return $this->getName();
        }

        return $this->title;
    }

    public function setTitle(string $title)
    {
        $this->title = $title;
    }

    public function isHidden(): bool
    {
        return $this->isHidden;
    }

    public function setIsHidden(bool $isHidden)
    {
        $this->isHidden = $isHidden;
    }
}

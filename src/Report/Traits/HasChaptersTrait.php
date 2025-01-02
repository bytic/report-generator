<?php

declare(strict_types=1);

namespace ByTIC\ReportGenerator\Report\Traits;

use ByTIC\ReportGenerator\ReportChapters\ReportChapter;
use ByTIC\ReportGenerator\ReportChapters\ReportChaptersCollection;

/**
 * Trait HasPerspectivesTrait.
 */
trait HasChaptersTrait
{
    /**
     * @var ReportChaptersCollection
     */
    protected $chapters = null;

    protected $chapterCurrent = null;

    /**
     * @param $name
     * @param string $label
     */
    public function createChapter($name, $label = ''): ReportChapter
    {
        $item = new ReportChapter();
        $item->setName($name);
        $item->setLabel($label);
        $this->getChapters()->add($item);

        return $item;
    }

    /**
     * @return ReportChaptersCollection
     */
    public function getChapters()
    {
        if (null === $this->chapters) {
            $this->initChapters();
        }

        return $this->chapters;
    }

    protected function initChapters()
    {
        $perspectives = new ReportChaptersCollection();
        $this->chapters = $perspectives;
    }

    /**
     * @param $perspective
     * @return ReportChapter|null
     */
    public function setChapter($perspective): ?ReportChapter
    {
        return $this->currentChapter($perspective);
    }

    /**
     * @return ReportChapter
     */
    public function currentChapter(string $item = null)
    {
        if (null !== $item) {
            $this->chapterCurrent = $item;
        }

        return $this->getCurrentChapter();
    }

    /**
     * @return ReportChapter
     */
    protected function getCurrentChapter(): ?ReportChapter
    {
        return $this->getChapters()->get($this->chapterCurrent);
    }
}

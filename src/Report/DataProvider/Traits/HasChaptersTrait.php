<?php

declare(strict_types=1);

namespace ByTIC\ReportGenerator\Report\DataProvider\Traits;

use ByTIC\ReportGenerator\ReportChapters\ReportChapter;
use ByTIC\ReportGenerator\ReportChapters\ReportChaptersCollection;

/**
 *
 */
trait HasChaptersTrait
{

    protected ?ReportChaptersCollection $chapters = null;

    public function getChapters(): ?ReportChaptersCollection
    {
        return $this->chapters;
    }

    /**
     * @param $chapters
     * @return void
     */
    protected function setChapters($chapters)
    {
        $this->chapters = $chapters;
    }

    /**
     * @param $key
     * @return ReportChapter|null
     */
    public function getChapter($key)
    {
        return $this->chapters->get($key);
    }
}
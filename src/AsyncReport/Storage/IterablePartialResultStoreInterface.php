<?php

declare(strict_types=1);

namespace ByTIC\ReportGenerator\AsyncReport\Storage;

use ByTIC\ReportGenerator\Report\DataProvider\DataRows\DataRow;

interface IterablePartialResultStoreInterface extends PartialResultStoreInterface
{
    /** @return iterable<DataRow> */
    public function iterateRows(string $jobId): iterable;
}

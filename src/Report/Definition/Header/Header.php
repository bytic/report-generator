<?php

declare(strict_types=1);

namespace ByTIC\ReportGenerator\Report\Definition\Header;

use ArrayAccess;
use ByTIC\ReportGenerator\Report\Definition\Header\Traits\ArrayMethodsTrait;
use ByTIC\ReportGenerator\Report\Definition\Header\Traits\HasColumnsTrait;
use ByTIC\ReportGenerator\Report\Definition\Header\Traits\HasRowsTrait;
use IteratorAggregate;

/**
 * Class Header.
 */
class Header implements IteratorAggregate, ArrayAccess
{
    use HasRowsTrait;
    use HasColumnsTrait;
    use ArrayMethodsTrait;
}

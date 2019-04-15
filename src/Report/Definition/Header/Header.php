<?php

namespace ByTIC\ReportGenerator\Report\Definition\Header;

use ArrayAccess;
use ByTIC\ReportGenerator\Report\Definition\Header\Traits\ArrayMethodsTrait;
use ByTIC\ReportGenerator\Report\Definition\Header\Traits\HasColumnsTrait;
use ByTIC\ReportGenerator\Report\Definition\Header\Traits\HasRowsTrait;
use IteratorAggregate;
use Traversable;

/**
 * Class Header
 * @package ByTIC\ReportGenerator\Report\Definition\Header
 */
class Header  implements IteratorAggregate, ArrayAccess
{
    use HasRowsTrait;
    use HasColumnsTrait;
    use ArrayMethodsTrait;
}

<?php

declare(strict_types=1);

namespace ByTIC\ReportGenerator\Tests\Utility;

use ByTIC\ReportGenerator\Tests\AbstractTest;
use ByTIC\ReportGenerator\Utility\PhpSpreadsheet;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;

/**
 * Class PhpSpreadsheetTest
 */
class PhpSpreadsheetTest extends AbstractTest
{
    public static function data_cleanSheetName(): array
    {
        return [
            ['Simple Name', 'Simple Name'],
            ['Name with * forbidden', 'Name with  forbidden'],
            ['Name with / forbidden', 'Name with  forbidden'],
            ['Name with \\ forbidden', 'Name with  forbidden'],
            ['Name with ? forbidden', 'Name with  forbidden'],
            ['Name with : forbidden', 'Name with  forbidden'],
            ['Name with [ forbidden', 'Name with  forbidden'],
            ['Name with ] forbidden', 'Name with  forbidden'],
            ["'Name with quotes'", "Name with quotes"],
            ['A very long sheet name that exceeds the thirty one characters limit', 'A very long sheet name that exc'],
            ['Sheet name with entities &amp;', 'Sheet name with entities &'],
            ['șțâîă', 'staia'],
            ['', 'Worksheet'],
            ['*?:', 'Worksheet'],
            [" 'Name with space and quotes' ", "Name with space and quotes"],
        ];
    }

    #[Test]
    #[DataProvider('data_cleanSheetName')]
    public function test_cleanSheetName($name, $expected)
    {
        self::assertSame($expected, PhpSpreadsheet::cleanSheetName($name));
    }
}

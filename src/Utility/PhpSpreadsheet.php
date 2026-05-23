<?php

declare(strict_types=1);

namespace ByTIC\ReportGenerator\Utility;

use Nip\Utility\Str;

/**
 *
 */
class PhpSpreadsheet
{
    /**
     * @param $name
     * @return string
     */
    public static function cleanSheetName($name): string
    {
        $name = html_entity_decode((string)$name, ENT_QUOTES, 'UTF-8');
        $name = Str::ascii($name);

        // Forbidden characters in Excel sheet names: \ / ? * [ ] :
        $name = str_replace(['\\', '/', '?', '*', '[', ']', ':'], '', $name);

        $name = trim($name, " '");

        $name = substr($name, 0, 31);

        if (empty($name)) {
            return 'Worksheet';
        }

        return $name;
    }
}


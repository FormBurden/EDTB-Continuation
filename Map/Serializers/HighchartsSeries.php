<?php
declare(strict_types=1);

namespace EDTB\Map\Serializers;

/**
 * Tiny helper to compose Highcharts series JS literals.
 */
final class HighchartsSeries
{
    /**
     * Build a single-series JS object literal (string).
     * $markerJs is expected to be a valid JS object fragment: marker:{...}
     */
    public static function entry(string $name, string $coordCsv, string $markerJs): string
    {
        return '{name:"' . self::escapeName($name) . '",data:[[' . $coordCsv . ']],' . $markerJs . '}';
    }

    public static function escapeName(string $s): string
    {
        return str_replace(['"', "\n", "\r"], ['\\"', ' ', ' '], $s);
    }
}

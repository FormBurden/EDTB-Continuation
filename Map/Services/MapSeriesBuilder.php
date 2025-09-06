<?php
declare(strict_types=1);

namespace EDTB\Map\Services;

require_once __DIR__ . '/../Serializers/HighchartsSeries.php';
require_once __DIR__ . '/Fetch/POIs.php';
require_once __DIR__ . '/Fetch/Bookmarks.php';
require_once __DIR__ . '/Fetch/Rares.php';
require_once __DIR__ . '/Fetch/Visited.php';

use EDTB\GalMap\GalMapParams;
use EDTB\Map\Services\Fetch\POIs;
use EDTB\Map\Services\Fetch\Bookmarks;
use EDTB\Map\Services\Fetch\Rares;
use EDTB\Map\Services\Fetch\Visited;

/**
 * Facade that composes the individual fetchers.
 */
final class MapSeriesBuilder
{
    /**
     * @param \mysqli       $mysqli
     * @param array         $settings expects: maxdistance, nmap_show_* keys
     * @param array         $curSys   expects: x,y,z,name
     * @param GalMapParams  $params
     * @return string       series entries joined by commas (JS literal, not JSON)
     */
    public static function build(\mysqli $mysqli, array $settings, array $curSys, GalMapParams $params): string
    {
        $pieces = [];

        if (self::truthy($settings['nmap_show_pois'] ?? 'true')) {
            $pieces = array_merge($pieces, POIs::fetch($mysqli, $settings, $curSys));
        }
        if (self::truthy($settings['nmap_show_bookmarks'] ?? 'true')) {
            $pieces = array_merge($pieces, Bookmarks::fetch($mysqli, $settings, $curSys));
        }
        if (self::truthy($settings['nmap_show_visited_systems'] ?? 'true')) {
            $pieces = array_merge($pieces, Visited::fetch($mysqli, $settings, $curSys));
        }

        return implode(',', $pieces);
    }

    private static function truthy($v): bool
    {
        if (is_bool($v)) return $v;
        return is_string($v) ? (strtolower($v) === 'true' || $v === '1') : false;
    }
}

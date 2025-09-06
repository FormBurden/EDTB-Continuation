<?php
declare(strict_types=1);

namespace EDTB\Map\Services\Fetch;

use EDTB\Map\Serializers\HighchartsSeries;

final class Bookmarks
{
    /** @return array<string> JS fragments */
    public static function fetch(\mysqli $mysqli, array $settings, array $curSys): array
    {
        $out = [];
        $q = 'SELECT
                    user_bookmarks.note AS comment,
                    UNIX_TIMESTAMP(user_bookmarks.created_at) AS added_on,
                    edtb_systems.name AS system_name,
                    edtb_systems.x, edtb_systems.y, edtb_systems.z,
                    \'Bookmark\' AS category_name
            FROM user_bookmarks
            LEFT JOIN edtb_systems
                ON user_bookmarks.system_name COLLATE utf8mb4_unicode_ci
                = edtb_systems.name COLLATE utf8mb4_unicode_ci';

        $res = $mysqli->query($q) or \write_log($mysqli->error, __FILE__, __LINE__);

        while ($row = $res->fetch_object()) {
            $sysName = $row->system_name;
            $x = $row->x; $y = $row->y; $z = $row->z;

            if (!\validCoordinates($x, $y, $z)) {
                $esc = $mysqli->real_escape_string($sysName);
                $coordRes = $mysqli->query(
                    "SELECT x,y,z FROM user_systems_own WHERE name = '$esc' LIMIT 1"
                ) or \write_log($mysqli->error, __FILE__, __LINE__);
                if ($coordObj = $coordRes->fetch_object()) {
                    $x = $coordObj->x; $y = $coordObj->y; $z = $coordObj->z;
                }
                $coordRes->close();
            }

            $dist = \validCoordinates($x, $y, $z) ? self::distanceIfValid($x, $y, $z, $curSys) : null;
            if ($dist !== null && $dist <= (float)$settings['maxdistance']) {
                $out[] = HighchartsSeries::entry($sysName, $x . ',' . $y . ',' . $z, 'marker:{symbol:"url(/style/img/bm.png)"}');
            }
        }
        $res->close();
        return $out;
    }

    private static function distanceIfValid($x, $y, $z, array $curSys): ?float
    {
        if (\validCoordinates($x, $y, $z) && \validCoordinates($curSys['x'], $curSys['y'], $curSys['z'])) {
            return sqrt((($x - $curSys['x']) ** 2) + (($y - $curSys['y']) ** 2) + (($z - $curSys['z']) ** 2));
        }
        return null;
    }
}

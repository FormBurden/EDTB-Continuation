<?php
declare(strict_types=1);

namespace EDTB\Map\Services\Fetch;

use EDTB\Map\Serializers\HighchartsSeries;

final class POIs
{
    /** @return array<string> JS fragments */
    public static function fetch(\mysqli $mysqli, array $settings, array $curSys): array
    {
        $out = [];
        $q = "SELECT up.name AS poi_name, up.system_name, es.x AS sx, es.y AS sy, es.z AS sz
            FROM user_poi AS up
            LEFT JOIN edtb_systems AS es ON up.system_name = es.name";

        $res = $mysqli->query($q) or \write_log($mysqli->error, __FILE__, __LINE__);
        while ($row = $res->fetch_object()) {
            $name     = $row->system_name;
            $dispName = $row->poi_name !== '' ? $row->poi_name : $row->system_name;
            $x = (float)$row->sx; $y = (float)$row->sy; $z = (float)$row->sz;
            $dist = self::distanceIfValid($x, $y, $z, $curSys);
            if ($dist !== null && $dist <= (float)$settings['maxdistance']) {
                $escName = $mysqli->real_escape_string($name);
                $visited = $mysqli->query(
                    "SELECT visit FROM user_visited_systems WHERE system_name = '$escName' ORDER BY visit ASC LIMIT 1"
                )->num_rows;

                $marker = 'marker:{symbol:"url(/style/img/goto.png)"}';
                if ($name === 'SOL') {
                    $marker = 'marker:{symbol:"circle",radius:3,fillColor:"#37bf1c"}';
                } elseif ($visited > 0) {
                    $marker = 'marker:{symbol:"url(/style/img/goto-g.png)"}';
                }

                $out[] = HighchartsSeries::entry($dispName, $x . ',' . $y . ',' . $z, $marker);
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

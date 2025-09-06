<?php
declare(strict_types=1);

namespace EDTB\Map\Services\Fetch;

use EDTB\Map\Serializers\HighchartsSeries;

final class Visited
{
    /** @return array<string> JS fragments */
    public static function fetch(\mysqli $mysqli, array $settings, array $curSys): array
    {
        $out = [];
        $q = 'SELECT
                  user_visited_systems.system_name AS system_name, user_visited_systems.visit,
                  edtb_systems.x, edtb_systems.y, edtb_systems.z, edtb_systems.id AS sysid, edtb_systems.allegiance
              FROM user_visited_systems
              LEFT JOIN edtb_systems ON user_visited_systems.system_name = edtb_systems.name
              GROUP BY user_visited_systems.system_name
              ORDER BY user_visited_systems.visit ASC';
        $res = $mysqli->query($q) or \write_log($mysqli->error, __FILE__, __LINE__);

        while ($row = $res->fetch_object()) {
            $name = $row->system_name;
            $x = (float)$row->x; $y = (float)$row->y; $z = (float)$row->z;
            $dist = self::distanceIfValid($x, $y, $z, $curSys);
            if ($dist !== null && $dist <= (float)$settings['maxdistance']) {
                $esc = $mysqli->real_escape_string($name);
                $loggedRes = $mysqli->query(
                    "SELECT id FROM user_log WHERE system_name = '$esc' LIMIT 1"
                );
                $logged = $loggedRes->num_rows;

                $color = self::allegianceColor($row->allegiance);
                $isCurrent = (strtolower($name) === strtolower($curSys['name'] ?? ''));

                if ($logged > 0 && !$isCurrent) {
                    $marker = 'marker:{symbol:"circle",radius:5,fillColor:"' . $color . '",lineWidth:"2",lineColor:"#2e92e7"}';
                } elseif ($isCurrent) {
                    $marker = 'marker:{symbol:"circle",radius:5,fillColor:"' . $color . '",lineWidth:"2",lineColor:"#f44b09"}';
                } else {
                    $marker = 'marker:{symbol:"circle",radius:3,fillColor:"' . $color . '"}';
                }

                $out[] = HighchartsSeries::entry($name, $x . ',' . $y . ',' . $z, $marker);
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

    private static function allegianceColor(?string $allegiance): string
    {
        switch ($allegiance) {
            case 'Empire':     return 'rgba(231, 216, 132, 0.7)';
            case 'Alliance':   return 'rgba(9, 180, 244, 0.7)';
            case 'Federation': return 'rgba(140, 140, 140, 0.7)';
            default:           return 'rgba(255, 255, 255, 0.8)';
        }
    }
}

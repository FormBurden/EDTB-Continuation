<?php
declare(strict_types=1);

namespace EDTB\Map\Services\Fetch;

use EDTB\Map\Serializers\HighchartsSeries;

final class Rares
{
    /** @return array<string> JS fragments */
    public static function fetch(\mysqli $mysqli, array $settings, array $curSys): array
    {
        $out = [];
        $q = "SELECT edtb_rares.item, edtb_rares.station, edtb_rares.system_name, edtb_rares.ls_to_star,
                     edtb_systems.x, edtb_systems.y, edtb_systems.z
              FROM edtb_rares
              LEFT JOIN edtb_systems ON edtb_rares.system_name = edtb_systems.name
              WHERE edtb_rares.system_name != ''";
        $res = $mysqli->query($q) or \write_log($mysqli->error, __FILE__, __LINE__);

        while ($row = $res->fetch_object()) {
            $item    = $row->item;
            $station = $row->station;
            $sys     = $row->system_name;
            $ls      = number_format((float)$row->ls_to_star);

            $label = $item . ' - ' . $sys . ' (' . $station . ' - ' . $ls . ' ls)';
            $x = (float)$row->x; $y = (float)$row->y; $z = (float)$row->z;

            $dist = self::distanceIfValid($x, $y, $z, $curSys);
            if ($dist !== null && $dist <= (float)$settings['maxdistance']) {
                $out[] = HighchartsSeries::entry($label, $x . ',' . $y . ',' . $z, 'marker:{symbol:"url(/style/img/rare.png)"}');
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

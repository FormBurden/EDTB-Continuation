<?php
declare(strict_types=1);

namespace EDTB\Domain\Rares;

final class RaresRepository
{
    /**
     * Return a mysqli_result for nearby rares (or false if table missing or query fails),
     * so the page can continue using fetch_object() and num_rows exactly as before.
     *
     * We replicate the original SQL including:
     *  - distance calculation,
     *  - left join to edtb_systems,
     *  - bounding box on x/y/z within +/- $range,
     *  - ORDER BY to show the current system first, then ascending distance,
     *  - LIMIT 10.
     */
    public static function selectNearbyRaresResult(
        \mysqli $mysqli,
        string $systemName,
        float $cx,
        float $cy,
        float $cz,
        float $range
    ): \mysqli_result|false {
        // Fresh DBs may not have edtb_rares yet; mirror original table-existence check
        $chk = $mysqli->query("SELECT 1 FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = 'edtb_rares'");
        if (!$chk) {
            // same failure semantics as before: return false so caller sets $raresCloseby = 0
            return false;
        }
        $has = $chk->num_rows > 0;
        $chk->close();
        if (!$has) {
            return false;
        }

        $escName = $mysqli->real_escape_string($systemName);

        // Build the exact same SQL the page had (SQL_CACHE kept as-is)
        $sql = '  SELECT SQL_CACHE
                    sqrt(
                        pow((edtb_systems.x-(' . $cx . ')),2)
                      + pow((edtb_systems.y-(' . $cy . ')),2)
                      + pow((edtb_systems.z-(' . $cz . ')),2)
                    ) AS distance,
                                        sqrt(
                        pow((edtb_systems.x-(' . $cx . ')),2)
                      + pow((edtb_systems.y-(' . $cy . ')),2)
                      + pow((edtb_systems.z-(' . $cz . ')),2)
                    ) AS distance,
                    edtb_rares.item, edtb_rares.system_name, edtb_rares.station,
                    edtb_rares.sc_est_mins, edtb_rares.ls_to_star,
                    edtb_rares.needs_permit, edtb_rares.max_landing_pad_size,
                    edtb_systems.x, edtb_systems.y, edtb_systems.z

                    FROM edtb_rares
                    LEFT JOIN edtb_systems ON edtb_rares.system_name = edtb_systems.name
                    WHERE
                    edtb_systems.x BETWEEN ' . ($cx - $range) . ' AND ' . ($cx + $range) . '
                    AND edtb_systems.y BETWEEN ' . ($cy - $range) . ' AND ' . ($cy + $range) . '
                    AND edtb_systems.z BETWEEN ' . ($cz - $range) . ' AND ' . ($cz + $range) . '
                    ORDER BY
                    edtb_rares.system_name = \'' . $escName . '\' DESC,
                    distance ASC
                    LIMIT 10';

        $res = $mysqli->query($sql) or write_log($mysqli->error, __FILE__, __LINE__);
        return $res; // may be false, caller handles num_rows vs. 0
    }
}

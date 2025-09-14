<?php
declare(strict_types=1);

namespace EDTB\Domain\Stations;

final class StationsRepository
{
    /**
     * Return a mysqli_result for stations belonging to a given system id.
     * Callers should iterate with fetch_object() for 1:1 rendering.
     *
     * @return \mysqli_result|false
     */
    public static function selectResultBySystemId(\mysqli $mysqli, int $systemId)
    {
        // Preferred/legacy table name
        $tbl = 'edtb_stations';

        // Try to normalize "distance_to_arrival" for ordering if schema varies.
        $hasDistArr = self::columnExists($mysqli, $tbl, 'distance_to_arrival');
        $hasDistStar = self::columnExists($mysqli, $tbl, 'distance_to_star');
        $hasLsFromStar = self::columnExists($mysqli, $tbl, 'ls_from_star');

        $select = "`{$tbl}`.*";
        if ($hasDistArr) {
            $select .= ", `{$tbl}`.`distance_to_arrival` AS `distance_to_arrival`";
        } elseif ($hasDistStar) {
            $select .= ", `{$tbl}`.`distance_to_star` AS `distance_to_arrival`";
        } elseif ($hasLsFromStar) {
            $select .= ", `{$tbl}`.`ls_from_star` AS `distance_to_arrival`";
        }

        // Station display name column
        $nameCol = self::firstExistingColumn($mysqli, $tbl, ['name','station','station_name','label']) ?? 'name';

        // System id column candidates
        $sidCol = self::firstExistingColumn($mysqli, $tbl, ['system_id','sys_id','systemId','systemid','id_system']) ?? 'system_id';

        // ORDER: known distance first, ASC; then by station name (collation for stable sort)
        $order = 'ORDER BY '
               . 'CASE WHEN `distance_to_arrival` IS NULL THEN 1 ELSE 0 END ASC, '
               . '`distance_to_arrival` ASC, '
               . "`{$nameCol}` COLLATE utf8mb4_unicode_ci ASC";

        $sql = 'SELECT ' . $select . " FROM `{$tbl}` "
             . "WHERE `{$sidCol}` = " . (int)$systemId . ' '
             . $order;

        return $mysqli->query($sql);
    }

    /**
     * Convenience wrapper returning array<\stdClass>.
     */
    public static function findBySystemId(\mysqli $mysqli, int $systemId): array
    {
        $rows = [];
        $res = self::selectResultBySystemId($mysqli, $systemId);
        if ($res instanceof \mysqli_result) {
            while ($obj = $res->fetch_object()) {
                $rows[] = $obj;
            }
            $res->close();
        }
        return $rows;
    }

    // ---- helpers -----------------------------------------------------------

    private static function columnExists(\mysqli $mysqli, string $table, string $column): bool
    {
        $esc = $mysqli->real_escape_string($column);
        $sql = "SHOW COLUMNS FROM `{$table}` LIKE '{$esc}'";
        $res = $mysqli->query($sql);
        $ok = ($res && $res->num_rows > 0);
        if ($res) { $res->close(); }
        return $ok;
    }

    private static function firstExistingColumn(\mysqli $mysqli, string $table, array $candidates): ?string
    {
        foreach ($candidates as $c) {
            if (self::columnExists($mysqli, $table, $c)) {
                return $c;
            }
        }
        return null;
    }
}

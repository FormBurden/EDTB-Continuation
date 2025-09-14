<?php
declare(strict_types=1);

namespace EDTB\Domain\Rares;

final class RaresRepository
{
    /**
     * Return nearby rares as a raw mysqli_result (or false on failure),
     * preserving legacy call patterns that iterate with fetch_object().
     *
     * @return \mysqli_result|false
     */
    public static function selectNearbyRaresResult(
        \mysqli $mysqli,
        string $systemName,
        float $cx,
        float $cy,
        float $cz,
        float $range
    ) {
        $raresTable = 'edtb_rares';
        $systemsTable = 'edtb_systems';

        $escName = $mysqli->real_escape_string($systemName);

        // Distance in LY between joined system and current coords
        $distanceExpr = sprintf(
            'SQRT( POW(`%s`.`x` - %.6f, 2) + POW(`%s`.`y` - %.6f, 2) + POW(`%s`.`z` - %.6f, 2) )',
            $systemsTable, $cx, $systemsTable, $cy, $systemsTable, $cz
        );

        // Column variants (kept minimal and deterministic for parity)
        $rareNameCol = self::firstExistingColumn($mysqli, $raresTable, ['rare','rare_name','name','label']) ?? 'rare';
        $rareSysCol  = self::firstExistingColumn($mysqli, $raresTable, ['system_name','system','sys_name']) ?? 'system_name';
        $rareStatCol = self::firstExistingColumn($mysqli, $raresTable, ['station','station_name','market']) ?? 'station';
        $priceCol    = self::firstExistingColumn($mysqli, $raresTable, ['price','avg_price','gal_price']); // optional

        $select = [
            "`{$raresTable}`.*",
            "{$distanceExpr} AS `distance`",
            "`{$systemsTable}`.`name` AS `__joined_system_name`"
        ];
        if ($priceCol !== null) {
            $select[] = "`{$raresTable}`.`{$priceCol}` AS `price`";
        }
        $selectSql = implode(', ', $select);

        $predicates = [
            sprintf("`%s`.`x` BETWEEN %.6f AND %.6f", $systemsTable, $cx - $range, $cx + $range),
            sprintf("`%s`.`y` BETWEEN %.6f AND %.6f", $systemsTable, $cy - $range, $cy + $range),
            sprintf("`%s`.`z` BETWEEN %.6f AND %.6f", $systemsTable, $cz - $range, $cz + $range),
        ];
        $whereSql = implode(' AND ', $predicates);

        $sql = sprintf(
            'SELECT %s FROM `%s` ' .
            'LEFT JOIN `%s` ON `%s`.`%s` = `%s`.`name` COLLATE utf8mb4_unicode_ci ' .
            'WHERE %s ' .
            'ORDER BY `%s`.`%s` = \'%s\' COLLATE utf8mb4_unicode_ci DESC, `distance` ASC ' .
            'LIMIT 10',
            $selectSql,
            $raresTable,
            $systemsTable, $raresTable, $rareSysCol, $systemsTable,
            $whereSql,
            $raresTable, $rareSysCol, $escName
        );

        $res = $mysqli->query($sql);
        if (!$res && function_exists('write_log')) {
            write_log($mysqli->error, __FILE__, __LINE__);
        }
        return $res;
    }

    /**
     * Compatibility alias used in some older call sites.
     */
    public static function selectNearby(
        \mysqli $mysqli,
        string $systemName,
        float $cx,
        float $cy,
        float $cz,
        float $range
    ) {
        return self::selectNearbyRaresResult($mysqli, $systemName, $cx, $cy, $cz, $range);
    }

    // ---- helpers -----------------------------------------------------------

    private static function firstExistingColumn(\mysqli $mysqli, string $table, array $candidates): ?string
    {
        foreach ($candidates as $c) {
            $esc = $mysqli->real_escape_string($c);
            $sql = "SHOW COLUMNS FROM `{$table}` LIKE '{$esc}'";
            $res = $mysqli->query($sql);
            $ok = ($res && $res->num_rows > 0);
            if ($res) { $res->close(); }
            if ($ok) { return $c; }
        }
        return null;
    }
}

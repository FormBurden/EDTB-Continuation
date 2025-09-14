<?php
declare(strict_types=1);

namespace EDTB\Domain\Powers;

final class PowersRepository
{
    /**
     * Return the HQ system name for a given Power (or null if unknown).
     * We detect the proper table and column names at runtime (no schema changes).
     */
    public static function getHQSystemName(\mysqli $mysqli, string $powerName): ?string
    {
        $powerName = trim($powerName);
        if ($powerName === '') {
            return null;
        }

        // Candidate tables (keep order stable, first hit wins)
        $tables = [
            'edtb_powers',
            'powerplay_powers',
            'pp_powers',
            'edtb_powerplay',
            'powerplay',          // some dumps use a generic name
        ];

        // Possible name columns in those tables
        $nameCols = ['name', 'power', 'power_name', 'title', 'label'];

        // Possible HQ columns we’ve seen in the wild
        $hqCols = [
            'hq_system',
            'home_system',
            'home_system_name',
            'capital',
            'capital_system',
            'headquarters',
            'hq',
        ];

        foreach ($tables as $table) {
            if (!self::tableExists($mysqli, $table)) {
                continue;
            }

            $nameCol = self::firstExistingColumn($mysqli, $table, $nameCols);
            $hqCol   = self::firstExistingColumn($mysqli, $table, $hqCols);

            if ($nameCol === null || $hqCol === null) {
                // This table exists but doesn't have the columns we need.
                continue;
            }

            $esc = $mysqli->real_escape_string($powerName);
            $sql = sprintf(
                "SELECT `%s` AS hq FROM `%s` WHERE `%s` = '%s' LIMIT 1",
                $hqCol,
                $table,
                $nameCol,
                $esc
            );

            $res = $mysqli->query($sql);
            if ($res && $res->num_rows > 0) {
                $row = $res->fetch_assoc();
                $res->close();
                $hq = $row['hq'] ?? null;
                return ($hq !== null && $hq !== '') ? (string)$hq : null;
            }
            if ($res) {
                $res->close();
            }
        }

        return null;
    }

    // ---- helpers (private) -------------------------------------------------

    private static function tableExists(\mysqli $mysqli, string $table): bool
    {
        $esc = $mysqli->real_escape_string($table);
        $sql = "SHOW TABLES LIKE '{$esc}'";
        $res = $mysqli->query($sql);
        $ok  = ($res && $res->num_rows > 0);
        if ($res) {
            $res->close();
        }
        return $ok;
    }

    private static function firstExistingColumn(\mysqli $mysqli, string $table, array $candidates): ?string
    {
        foreach ($candidates as $col) {
            $escCol = $mysqli->real_escape_string($col);
            $sql    = "SHOW COLUMNS FROM `{$table}` LIKE '{$escCol}'";
            $res    = $mysqli->query($sql);
            $ok     = ($res && $res->num_rows > 0);
            if ($res) {
                $res->close();
            }
            if ($ok) {
                return $col;
            }
        }
        return null;
    }
}

<?php
declare(strict_types=1);

namespace EDTB\Domain\Powers;

final class PowersRepository
{
    /**
     * Returns the HQ system_name for a given Power (or null if not found).
     * Mirrors the inline SQL semantics used previously (same table/columns/limit).
     */
    public static function getHQSystemName(\mysqli $mysqli, string $powerName): ?string
    {
        $esc = $mysqli->real_escape_string($powerName);

        $sql = "SELECT system_name
                FROM edtb_powers
                WHERE name = '$esc'
                LIMIT 1";

        $res = $mysqli->query($sql) or write_log($mysqli->error, __FILE__, __LINE__);

        if ($res && $res->num_rows > 0) {
            $obj = $res->fetch_object();
            $hq  = isset($obj->system_name) ? (string)$obj->system_name : null;
            $res->close();
            return $hq;
        }

        if ($res) {
            $res->close();
        }
        return null;
    }
}

<?php
declare(strict_types=1);

namespace EDTB\Domain\System;

final class SystemRepository
{
    /**
     * Minimal read: returns the same stdClass shape System/getData_systemInfo.php expects.
     * Uses mysqli to match the current code path (no new DB layer yet).
     */
    public static function findById(\mysqli $mysqli, int $systemId): ?\stdClass
    {
        // population column is optional in some schemas; match current behavior
        $hasPopulationCol = false;
        if ($chk = $mysqli->query("SHOW COLUMNS FROM edtb_systems LIKE 'population'")) {
            $hasPopulationCol = ($chk->num_rows > 0);
            $chk->close();
        }

        // Keep field aliases that downstream code already uses (si_system_coordx/y/z)
        $baseFields = "id, name, allegiance, economy, government, ruling_faction, state, security, power, power_state, x AS si_system_coordx, y AS si_system_coordy, z AS si_system_coordz, simbad_ref";
        $fields = $hasPopulationCol ? ("population, " . $baseFields) : $baseFields;

        $systemId = (int)$systemId;
        $query = "SELECT SQL_CACHE $fields FROM edtb_systems WHERE id = '$systemId' LIMIT 1";

        $result = $mysqli->query($query);
        if (!$result) {
            // Preserve current behavior: caller will use defaults if null/empty
            return null;
        }
        $obj = $result->fetch_object();
        $result->close();

        return $obj ?: null;
    }
        /**
     * Fetch id/x/y/z for a system name from edtb_systems,
     * falling back to user_systems_own if not found.
     * Mirrors the previous inline behavior (same SQL and error logging).
     */
    public static function findCoordsByNameOrUserOwn(\mysqli $mysqli, string $name): ?\stdClass
    {
        $esc = $mysqli->real_escape_string($name);

        // Primary: edtb_systems (keep LIMIT 1 and columns used by caller)
        $q1 = "SELECT id, x, y, z
               FROM edtb_systems
               WHERE name = '$esc'
               LIMIT 1";

        $res = $mysqli->query($q1) or write_log($mysqli->error, __FILE__, __LINE__);
        if ($res && $res->num_rows > 0) {
            $obj = $res->fetch_object();
            $res->close();
            return $obj;
        }
        if ($res) {
            $res->close();
        }

        // Fallback: user_systems_own (no id column there in the legacy schema)
        $q2 = "SELECT x, y, z
               FROM user_systems_own
               WHERE name = '$esc'
               LIMIT 1";

        $res2 = $mysqli->query($q2) or write_log($mysqli->error, __FILE__, __LINE__);
        if ($res2 && $res2->num_rows > 0) {
            $obj2 = $res2->fetch_object();
            // Maintain the variables the caller uses; id may not exist here.
            if (!isset($obj2->id)) {
                $o = new \stdClass();
                $o->id = null;
                $o->x  = $obj2->x;
                $o->y  = $obj2->y;
                $o->z  = $obj2->z;
                $res2->close();
                return $o;
            }
            $res2->close();
            return $obj2;
        }
        if ($res2) {
            $res2->close();
        }

        // No match in either table
        return null;
    }
    /**
     * Find the system id by exact name from edtb_systems.
     * Returns the id as int, or null if not found.
     * Mirrors the previous inline behavior and logging.
     */
    public static function findIdByName(\mysqli $mysqli, string $name): ?int
    {
        $esc = $mysqli->real_escape_string($name);

        $sql = "SELECT id
                FROM edtb_systems
                WHERE name = '$esc'
                LIMIT 1";

        $res = $mysqli->query($sql) or write_log($mysqli->error, __FILE__, __LINE__);
        if ($res && $res->num_rows > 0) {
            $obj = $res->fetch_object();
            $id  = isset($obj->id) ? (int)$obj->id : null;
            $res->close();
            return $id;
        }

        if ($res) {
            $res->close();
        }
        return null;
    }


}

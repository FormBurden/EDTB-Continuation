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
}

<?php
declare(strict_types=1);

namespace EDTB\Domain\Stations;

final class StationsRepository
{
    /**
     * Return a mysqli_result of stations for a given system id, ordered to match the original UI:
     * - Primary by -ls_from_star DESC (unknown/0 last)
     * - Secondary by name ASC
     *
     * Caller is responsible for closing the result.
     */
    public static function selectResultBySystemId(\mysqli $mysqli, int $systemId): ?\mysqli_result
    {
        $systemId = (int)$systemId;
        if ($systemId <= 0) {
            return null;
        }

        // Broad column set to satisfy presentation without extra queries.
        $sql = "
        SELECT
            id,
            system_id,
            name,
            type,
            is_planetary,
            allegiance,
            government,
            state,
            economies,
            max_landing_pad_size,
            ls_from_star,
            ls_from_star AS distance_to_arrival,   -- alias for callers that expected this name
            commodities_market,
            black_market,
            outfitting,
            shipyard,
            refuel,
            repair,
            rearm AS restock,                      -- alias to match UI/service 'restock'
            selling_ships,
            selling_modules,
            import_commodities,
            export_commodities,
            prohibited_commodities,
            outfitting_updated_at,
            shipyard_updated_at,
            created_at,
            updated_at
        FROM edtb_stations
        WHERE system_id = {$systemId}
        ORDER BY -ls_from_star DESC, name
        ";


        $res = $mysqli->query($sql);
        if (!$res) {
            return null;
        }
        return $res;
    }
}

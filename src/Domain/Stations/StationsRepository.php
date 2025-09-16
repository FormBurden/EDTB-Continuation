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
                name,
                type,
                is_planetary,
                allegiance,
                government,
                economy,
                second_economy,
                secondary_economy,
                primary_economy,
                max_landing_pad_size,
                distance_to_arrival,
                ls_from_star,
                commodities_market,
                black_market,
                outfitting,
                shipyard,
                refuel,
                repair,
                restock,
                pioneer_supplies,
                interstellar_factors,
                apex,
                frontline,
                vista_genomics,
                bartender,
                redemption_office,
                selling_ships,
                selling_modules
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

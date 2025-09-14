<?php
class NearestSystemsQuery
{
    /** @var \mysqli */
    private $mysqli;
    private $useX;
    private $useY;
    private $useZ;

    public function __construct($mysqli, $useX, $useY, $useZ)
    {
        $this->mysqli = $mysqli;
        $this->useX = $useX;
        $this->useY = $useY;
        $this->useZ = $useZ;
    }

    private function tableExists(string $table): bool
    {
        $res = @$this->mysqli->query("SHOW TABLES LIKE '" . $this->mysqli->real_escape_string($table) . "'");
        if ($res === false) return false;
        $ok = $res->num_rows > 0;
        $res->close();
        return $ok;
    }

    /**
     * Build the full SQL + whether we’re returning stations or systems.
     * Returns: ['sql' => string, 'stations' => bool]
     */
    public function build(array $get, string $addToQuery): array
    {
        // Determine if we’re doing stations view (requested + table exists)
        $stationsRequested = !empty($get['stations']);
        $stations = $stationsRequested && $this->tableExists('edtb_stations');

        // Start SELECT
        if ($stations) {
            $sql = "SELECT
                        edtb_stations.system_id AS system_id,
                        edtb_stations.name AS station_name,
                        edtb_stations.ls_from_star,
                        edtb_stations.max_landing_pad_size,
                        edtb_stations.is_planetary,
                        edtb_stations.type,
                        edtb_stations.id AS station_id,
                        edtb_stations.faction AS station_faction,
                        edtb_stations.government AS station_government,
                        edtb_stations.allegiance AS station_allegiance,
                        edtb_stations.state AS station_state,
                        edtb_stations.black_market,
                        edtb_stations.commodities_market,
                        edtb_stations.refuel,
                        edtb_stations.repair,
                        edtb_stations.rearm,
                        edtb_stations.outfitting,
                        edtb_stations.shipyard,
                        edtb_stations.import_commodities,
                        edtb_stations.export_commodities,
                        edtb_stations.prohibited_commodities,
                        edtb_stations.economies,
                        edtb_stations.shipyard_updated_at,
                        edtb_stations.outfitting_updated_at,
                        edtb_stations.selling_ships,
                        edtb_systems.allegiance AS allegiance,
                        edtb_systems.name AS system,
                        edtb_systems.x AS coordx,
                        edtb_systems.y AS coordy,
                        edtb_systems.z AS coordz,
                        edtb_systems.population,
                        edtb_systems.government,
                        edtb_systems.security,
                        edtb_systems.economy
                    FROM edtb_stations
                    LEFT JOIN edtb_systems ON edtb_stations.system_id = edtb_systems.id
                    WHERE edtb_systems.x != ''" . $addToQuery;

            // Ship name / outfitting group filters (escaped)
            if (!empty($get['ship_name'])) {
                $shipName = $this->mysqli->real_escape_string($get['ship_name']);
                $sql .= " AND edtb_stations.selling_ships LIKE '%{$shipName}%'";
            }
            if (!empty($get['group_id'])) {
                $groupId = (int)$get['group_id'];
                $sql .= " AND edtb_stations.outfitting LIKE '%Group {$groupId}%'";
            }
        } else {
            $sql = "SELECT
                        edtb_systems.id AS system_id,
                        edtb_systems.name AS system,
                        edtb_systems.x AS coordx,
                        edtb_systems.y AS coordy,
                        edtb_systems.z AS coordz,
                        edtb_systems.allegiance AS allegiance,
                        edtb_systems.government,
                        edtb_systems.security,
                        edtb_systems.economy,
                        edtb_systems.population
                    FROM edtb_systems
                    WHERE x != ''";
        }

        // Drop columns if lookup tables aren’t present (behavior mirrored from original)
        if (!$this->tableExists('edtb_systems_allegiance')) {
            $sql = str_replace('edtb_systems.allegiance AS allegiance, ', ' ', $sql);
        }
        if (!$this->tableExists('edtb_systems_government')) {
            $sql = str_replace('edtb_systems.government, ', ' ', $sql);
        }
        if (!$this->tableExists('edtb_systems_security')) {
            $sql = str_replace('edtb_systems.security, ', ' ', $sql);
        }
        if (!$this->tableExists('edtb_systems_economy')) {
            $sql = str_replace('edtb_systems.economy, ', ' ', $sql);
        }

        // Distance ring (same formula as before)
        $distance = isset($get['distance']) ? round(abs((float)$get['distance'])) : 50;
        $distance = $distance > 0 ? (int)$distance : 50;

        $sql .= " AND (SQRT(POW(edtb_systems.x - {$this->useX}, 2) + POW(edtb_systems.y - {$this->useY}, 2) + POW(edtb_systems.z - {$this->useZ}, 2)) <= {$distance})";


        // Allegiance filters
        if (!empty($get['system_allegiance']) && $get['system_allegiance'] !== '0') {
            $sa = $this->mysqli->real_escape_string($get['system_allegiance']);
            $sql .= " AND edtb_systems.allegiance = '{$sa}'";
        }
        if (!empty($get['allegiance']) && $get['allegiance'] !== '0') {
            $al = $this->mysqli->real_escape_string($get['allegiance']);
            $sql .= " AND edtb_systems.allegiance = '{$al}'";
        }

        // Permit
        if (!empty($get['permit']) && $get['permit'] !== '0') {
            $pm = $this->mysqli->real_escape_string($get['permit']);
            $sql .= " AND edtb_systems.requires_permit = '{$pm}'";
        }

        // Powers
        if (!empty($get['power']) && $get['power'] !== '0') {
            $pw = $this->mysqli->real_escape_string($get['power']);
            $sql .= " AND edtb_systems.power = '{$pw}'";
        }
        if (!empty($get['power_power']) && $get['power_power'] !== '0') {
            $ps = $this->mysqli->real_escape_string($get['power_power']);
            $sql .= " AND edtb_systems.power_state = '{$ps}'";
        }

        // Govt / Sec / Eco
        if (!empty($get['government']) && $get['government'] !== '0') {
            $gv = $this->mysqli->real_escape_string($get['government']);
            $sql .= " AND edtb_systems.government = '{$gv}'";
        }
        if (!empty($get['security']) && $get['security'] !== '0') {
            $sc = $this->mysqli->real_escape_string($get['security']);
            $sql .= " AND edtb_systems.security = '{$sc}'";
        }
        if (!empty($get['economy']) && $get['economy'] !== '0') {
            $ec = $this->mysqli->real_escape_string($get['economy']);
            $sql .= " AND edtb_systems.economy = '{$ec}'";
        }

        // Population min/max
        if (isset($get['min_population']) && $get['min_population'] !== '') {
            $sql .= " AND edtb_systems.population >= " . (int)$get['min_population'];
        }
        if (isset($get['max_population']) && $get['max_population'] !== '') {
            $sql .= " AND edtb_systems.population <= " . (int)$get['max_population'];
        }

        // Undiscovered
        if (!empty($get['undiscovered'])) {
            $sql .= " AND edtb_systems.is_populated = 0";
        }

        // Allow unknowns
        if (!empty($get['allegiance_unknown'])) {
            $sql .= " AND (edtb_systems.allegiance = '' OR edtb_systems.allegiance IS NULL)";
        }
        if (!empty($get['economy_unknown'])) {
            $sql .= " AND (edtb_systems.economy = '' OR edtb_systems.economy IS NULL)";
        }
        if (!empty($get['government_unknown'])) {
            $sql .= " AND (edtb_systems.government = '' OR edtb_systems.government IS NULL)";
        }
        if (!empty($get['security_unknown'])) {
            $sql .= " AND (edtb_systems.security = '' OR edtb_systems.security IS NULL)";
        }

        // Has stations?
        if (!empty($get['has_stations'])) {
            $sql .= " AND EXISTS (SELECT 1 FROM edtb_stations s WHERE s.system_id = edtb_systems.id)";
        }

        // Order by Euclidean distance
        $sql .= " ORDER BY (SQRT(POW(edtb_systems.x - {$this->useX}, 2) + POW(edtb_systems.y - {$this->useY}, 2) + POW(edtb_systems.z - {$this->useZ}, 2))) ASC";

        return ['sql' => $sql, 'stations' => $stations];
    }
}

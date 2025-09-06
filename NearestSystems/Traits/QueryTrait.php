<?php
/** Auto-extracted trait from NearestSystems.php to reduce file size. */
trait NearestSystemsQueryTrait
{
    private function getQuery()
    {
        /**
         * get url parameters
         */
        $shipName = $_GET['ship_name'] ?? '';
        $groupId = $_GET['group_id'] ?? '';
        /**
         * nearest stations....
         */
        // If stations have been requested but the table doesn't exist yet, fall back to systems.
        if ($this->stations !== false && !$this->tableExists('edtb_stations')) {
            $this->stations = false;
        }

        if ($this->stations !== false) {
            $this->mainQuery = "   SELECT edtb_stations.system_id AS system_id, edtb_stations.name AS station_name,
                                    edtb_stations.ls_from_star, edtb_stations.max_landing_pad_size,
                                    edtb_stations.is_planetary, edtb_stations.type,
                                    edtb_stations.id AS station_id, edtb_stations.faction AS station_faction,
                                    edtb_stations.government AS station_government, edtb_stations.allegiance AS station_allegiance,
                                    edtb_stations.state AS station_state, edtb_stations.black_market, edtb_stations.commodities_market,
                                    edtb_stations.refuel, edtb_stations.repair, edtb_stations.rearm,
                                    edtb_stations.outfitting, edtb_stations.shipyard,
                                    edtb_stations.import_commodities, edtb_stations.export_commodities,
                                    edtb_stations.prohibited_commodities, edtb_stations.economies, edtb_stations.shipyard_updated_at,
                                    edtb_stations.outfitting_updated_at, edtb_stations.selling_ships,
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
                                    WHERE edtb_systems.x != ''" . $this->addToQuery;

            if (!empty($shipName)) {
                $shipName = $this->mysqli->real_escape_string($shipName);

                $this->mainQuery .= " AND edtb_stations.selling_ships LIKE '%$shipName%'";
            }

            if (!empty($groupId)) {
                $groupId = (int)$groupId;

                $this->mainQuery .= " AND edtb_stations.outfitting LIKE '%Group $groupId%'";
            }
        } else {
            /**
             * nearest systems....
             */
            $this->mainQuery = "   SELECT edtb_systems.id AS system_id, edtb_systems.name AS system,
                                    edtb_systems.x AS coordx, edtb_systems.y AS coordy, edtb_systems.z AS coordz,
                                    edtb_systems.allegiance AS allegiance, edtb_systems.government, edtb_systems.security,
                                    edtb_systems.economy, edtb_systems.population
                                    FROM edtb_systems
                                    WHERE x != ''";
        }

        /**
         * not all systems have an allegiance
         */
        if (!$this->tableExists('edtb_systems_allegiance')) {
            $this->mainQuery = str_replace('edtb_systems.allegiance AS allegiance, ', ' ', $this->mainQuery);
        }

        /**
         * not all systems have government
         */
        if (!$this->tableExists('edtb_systems_government')) {
            $this->mainQuery = str_replace('edtb_systems.government, ', ' ', $this->mainQuery);
        }

        /**
         * not all systems have security
         */
        if (!$this->tableExists('edtb_systems_security')) {
            $this->mainQuery = str_replace('edtb_systems.security, ', ' ', $this->mainQuery);
        }

        /**
         * not all systems have economy
         */
        if (!$this->tableExists('edtb_systems_economy')) {
            $this->mainQuery = str_replace('edtb_systems.economy, ', ' ', $this->mainQuery);
        }
    }
}

<?php
/** Auto-extracted trait from NearestSystems.php to reduce file size. */
trait NearestSystemsParamsTrait
{
    private function getQueryParams()
    {
        /**
         * get url parameters
         */
        $facility = $_GET['facility'] ?? '';
        $only = $_GET['allegiance'] ?? '';
        $systemAllegiance = $_GET['system_allegiance'] ?? '';
        $power = $_GET['power'] ?? '';
        $pad = $_GET['pad'] ?? '';
        $stationType = $_GET['station_type'] ?? '';

        /**
         * specific power
         */
        if (!empty($power)) {
            $this->powerParams .= '&power=' . $power;
        }

        /**
         * specific allegiance
         */
        if (!empty($only)) {
            $this->allegianceParams .= '&allegiance=' . $only;
        }

        /**
         * specific system allegiance
         */
        if (!empty($systemAllegiance)) {
            $this->allegianceParams .= '&system_allegiance=' . $systemAllegiance;
        }

        /**
         * specific module or ship classes
         */
        $class = $_GET['class'] ?? '';
        if ($class !== '' && $class != '0') {
            $this->addToQuery .= " AND class = '" . $class . "'";
        }

        $rating = $_GET['rating'] ?? '';
        if ($rating !== '' && $rating != '0') {
            $this->addToQuery .= " AND rating = '" . $rating . "'";
        }

        /**
         * stuff on the station
         */
        if (!empty($facility) && $facility !== '0') {
            $addFacility = $facility;
            $this->addToQuery .= " AND $addFacility = '1'";
        }

        /**
         * station type
         */
        if (!empty($stationType) && $stationType !== '0') {
            $addStationType = $stationType;
            $this->addToQuery .= " AND edtb_stations.type = '" . $addStationType . "'";
        }

        /**
         * pad size
         */
        if (!empty($pad) && $pad !== '0') {
            $addPad = $pad;
            $this->addToQuery .= " AND edtb_stations.max_landing_pad_size = '" . $addPad . "'";
        }

        /**
         * system allegiance
         */
        if (!empty($systemAllegiance) && $systemAllegiance !== '0') {
            $this->addToQuery .= " AND edtb_systems.allegiance = '" . $systemAllegiance . "'";
        }

        /**
         * station allegiance
         */
        if (!empty($only) && $only !== '0') {
            $this->addToQuery .= " AND edtb_stations.allegiance = '" . $only . "'";
        }

        /**
         * shipping and outfitting filters
         */
        $facilityFilter = $_GET['facility_filter'] ?? '';
        $shipFilter = $_GET['ship_filter'] ?? '';
        $moduleFilter = $_GET['module_filter'] ?? '';

        if (!empty($facilityFilter)) {
            $this->addToQuery .= " AND (edtb_stations.$facilityFilter = 1)";
        }

        if (!empty($shipFilter)) {
            $this->addToQuery .= " AND (edtb_stations.selling_ships LIKE '%" . $shipFilter . "%')";
        }

        if (!empty($moduleFilter)) {
            $this->addToQuery .= " AND (edtb_stations.outfitting LIKE '%" . $moduleFilter . "%')";
        }
    }
}

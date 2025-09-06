<?php
/** Auto-extracted trait from NearestSystems.php to reduce file size. */
trait NearestSystemsFiltersTrait
{
    private function filters()
    {
        /**
         * get url parameters
         */
        $only = $_GET['allegiance'] ?? '';
        $systemAllegiance = $_GET['system_allegiance'] ?? '';
        $onlyGovernment = $_GET['government'] ?? '';
        $onlySecurity = $_GET['security'] ?? '';
        $onlyEconomy = $_GET['economy'] ?? '';
        $power = $_GET['power'] ?? '';
        $powerpower = $_GET['power_power'] ?? ''; /** <- for Contested / Exploited etc. */
        $distance = $_GET['distance'] ?? '50';
        $permit = $_GET['permit'] ?? '';
        $stations = $_GET['stations'] ?? '';
        $minPopulation = $_GET['min_population'] ?? '';
        $maxPopulation = $_GET['max_population'] ?? '';
        $undiscovered = $_GET['undiscovered'] ?? '';
        $allegianceUnknown = $_GET['allegiance_unknown'] ?? '';
        $economyUnknown = $_GET['economy_unknown'] ?? '';
        $governmentUnknown = $_GET['government_unknown'] ?? '';
        $securityUnknown = $_GET['security_unknown'] ?? '';
        $minorFaction = $_GET['minor_faction'] ?? '';
        $hasStations = $_GET['has_stations'] ?? '';

        /**
         * add range to the query
         */
        $distance = round(abs($distance));
        $distance = $distance > 0 ? $distance : '50';

        $this->mainQuery .= " AND (3959 * acos(least(1, (sin(radians({$this->useY})) * sin(radians(edtb_systems.y)) + cos(radians({$this->useY})) * cos(radians(edtb_systems.y)) * cos(radians(edtb_systems.x - {$this->useX})) )))) <= $distance";

        /**
         * system allegiance
         */
        if (!empty($systemAllegiance) && $systemAllegiance !== '0') {
            $this->mainQuery .= " AND edtb_systems.allegiance = '" . $systemAllegiance . "'";
        }

        /**
         * allegiance
         */
        if (!empty($only) && $only !== '0') {
            $this->mainQuery .= " AND edtb_systems.allegiance = '" . $only . "'";
        }

        /**
         * permit
         */
        if (!empty($permit) && $permit !== '0') {
            $this->mainQuery .= " AND edtb_systems.requires_permit = '" . $permit . "'";
        }

        /**
         * powers
         */
        if (!empty($power) && $power !== '0') {
            $this->mainQuery .= " AND edtb_systems.power = '" . $power . "'";
        }
        if (!empty($powerpower) && $powerpower !== '0') {
            $this->mainQuery .= " AND edtb_systems.power_state = '" . $powerpower . "'";
        }

        /**
         * government
         */
        if (!empty($onlyGovernment) && $onlyGovernment !== '0') {
            $this->mainQuery .= " AND edtb_systems.government = '" . $onlyGovernment . "'";
        }

        /**
         * security
         */
        if (!empty($onlySecurity) && $onlySecurity !== '0') {
            $this->mainQuery .= " AND edtb_systems.security = '" . $onlySecurity . "'";
        }

        /**
         * economy
         */
        if (!empty($onlyEconomy) && $onlyEconomy !== '0') {
            $this->mainQuery .= " AND edtb_systems.economy = '" . $onlyEconomy . "'";
        }

        /**
         * population min / max
         */
        if ($minPopulation !== '') {
            $this->mainQuery .= " AND edtb_systems.population >= " . (int)$minPopulation;
        }
        if ($maxPopulation !== '') {
            $this->mainQuery .= " AND edtb_systems.population <= " . (int)$maxPopulation;
        }

        /**
         * undiscovered systems
         */
        if (!empty($undiscovered)) {
            $this->mainQuery .= " AND edtb_systems.is_populated = 0";
        }

        /**
         * allow unknown filters
         */
        if (!empty($allegianceUnknown)) {
            $this->mainQuery .= " AND (edtb_systems.allegiance = '' OR edtb_systems.allegiance IS NULL)";
        }
        if (!empty($economyUnknown)) {
            $this->mainQuery .= " AND (edtb_systems.economy = '' OR edtb_systems.economy IS NULL)";
        }
        if (!empty($governmentUnknown)) {
            $this->mainQuery .= " AND (edtb_systems.government = '' OR edtb_systems.government IS NULL)";
        }
        if (!empty($securityUnknown)) {
            $this->mainQuery .= " AND (edtb_systems.security = '' OR edtb_systems.security IS NULL)";
        }

        /**
         * has stations?
         */
        if (!empty($hasStations)) {
            $this->mainQuery .= " AND EXISTS (SELECT 1 FROM edtb_stations s WHERE s.system_id = edtb_systems.id)";
        }

        /**
         * only stations
         */
        if (!empty($stations)) {
            $this->stations = true;
        } else {
            $this->stations = false;
        }

        /**
         * order by
         */
        $this->mainQuery .= " ORDER BY (SQRT(POW(edtb_systems.x - {$this->useX}, 2) + POW(edtb_systems.y - {$this->useY}, 2) + POW(edtb_systems.z - {$this->useZ}, 2))) ASC";
    }
}

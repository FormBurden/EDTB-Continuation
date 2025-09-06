<?php
class NearestSystemsParams
{
    /**
     * Mirrors the original getQueryParams() behavior:
     * - returns ['addToQuery', 'powerParams', 'allegianceParams']
     * - does NOT escape (kept 1:1 with original)
     */
    public static function build(array $get): array
    {
        $addToQuery = '';
        $powerParams = '';
        $allegianceParams = '';

        // Basic GETs
        $facility         = $get['facility'] ?? '';
        $only             = $get['allegiance'] ?? '';
        $systemAllegiance = $get['system_allegiance'] ?? '';
        $power            = $get['power'] ?? '';
        $pad              = $get['pad'] ?? '';
        $stationType      = $get['station_type'] ?? '';

        // Specific power → param string (unchanged)
        if (!empty($power)) {
            $powerParams .= '&power=' . $power;
        }

        // Allegiance → param string (unchanged)
        if (!empty($only)) {
            $allegianceParams .= '&allegiance=' . $only;
        }
        if (!empty($systemAllegiance)) {
            $allegianceParams .= '&system_allegiance=' . $systemAllegiance;
        }

        // Class / Rating
        $class = $get['class'] ?? '';
        if ($class !== '' && $class != '0') {
            $addToQuery .= " AND class = '" . $class . "'";
        }
        $rating = $get['rating'] ?? '';
        if ($rating !== '' && $rating != '0') {
            $addToQuery .= " AND rating = '" . $rating . "'";
        }

        // Facilities on station
        if (!empty($facility) && $facility !== '0') {
            $addFacility = $facility;
            $addToQuery .= " AND $addFacility = '1'";
        }

        // Station type
        if (!empty($stationType) && $stationType !== '0') {
            $addStationType = $stationType;
            $addToQuery .= " AND edtb_stations.type = '" . $addStationType . "'";
        }

        // Pad size
        if (!empty($pad) && $pad !== '0') {
            $addPad = $pad;
            $addToQuery .= " AND edtb_stations.max_landing_pad_size = '" . $addPad . "'";
        }

        // System allegiance
        if (!empty($systemAllegiance) && $systemAllegiance !== '0') {
            $addToQuery .= " AND edtb_systems.allegiance = '" . $systemAllegiance . "'";
        }

        // Station allegiance
        if (!empty($only) && $only !== '0') {
            $addToQuery .= " AND edtb_stations.allegiance = '" . $only . "'";
        }

        // Shipping / Outfitting filters
        $facilityFilter = $get['facility_filter'] ?? '';
        $shipFilter     = $get['ship_filter'] ?? '';
        $moduleFilter   = $get['module_filter'] ?? '';

        if (!empty($facilityFilter)) {
            $addToQuery .= " AND (edtb_stations.$facilityFilter = 1)";
        }
        if (!empty($shipFilter)) {
            $addToQuery .= " AND (edtb_stations.selling_ships LIKE '%" . $shipFilter . "%')";
        }
        if (!empty($moduleFilter)) {
            $addToQuery .= " AND (edtb_stations.outfitting LIKE '%" . $moduleFilter . "%')";
        }

        return [
            'addToQuery'       => $addToQuery,
            'powerParams'      => $powerParams,
            'allegianceParams' => $allegianceParams,
        ];
    }
}

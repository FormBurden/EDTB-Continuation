<?php
/**
 * DataPoint per-table configuration
 * - 'list'   = which columns to show in the list view (order kept)
 * - 'labels' = friendly header overrides for those columns
 * - 'order_by' (optional) = default ORDER BY clause (if supported by vendor)
 */
function datapoint_config_for_table(string $table): array
{
    switch ($table) {
        case 'edtb_systems':
            return [
                'list' => ['id','name','allegiance','government','economy','population','needs_permit'],
                'labels' => [
                    'id'            => 'Id',
                    'name'          => 'System',
                    'allegiance'    => 'Allegiance',
                    'government'    => 'Government',
                    'economy'       => 'Economy',
                    'population'    => 'Population',
                    'needs_permit'  => 'Permit Required',
                ],
                'order_by' => 'name ASC',
            ];

        case 'edtb_stations':
            return [
                'list' => ['id','name','type','max_landing_pad_size','has_blackmarket','has_outfitting','has_shipyard'],
                'labels' => [
                    'id'                    => 'Id',
                    'name'                  => 'Station',
                    'type'                  => 'Type',
                    'max_landing_pad_size'  => 'Max Pad',
                    'has_blackmarket'       => 'Black Market',
                    'has_outfitting'        => 'Outfitting',
                    'has_shipyard'          => 'Shipyard',
                ],
                'order_by' => 'name ASC',
            ];

        default:
            return []; // fallback: auto from Schema.php
    }
}

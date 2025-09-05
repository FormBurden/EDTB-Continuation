<?php
/**
 * DataPoint per-table configuration + tab titles + quick filters + presets
 * - 'list'     = columns to show in list view (order kept)
 * - 'labels'   = header overrides for those columns
 * - 'order_by' = default ORDER BY
 * - 'format'   = column => type ('bool')
 * - 'quick_filters' = array of groups: ['label'=>..., 'items'=>[ ['label'=>..,'field'=>..,'value'=>..,'op'=>..,'icon'=>..], ... ]]
 * - 'presets'  = name => [field, field, ...]
 */

function datapoint_table_title(string $table): string
{
    switch ($table) {
        case 'edtb_systems':    return 'Systems';
        case 'edtb_stations':   return 'Stations';
        case 'edtb_facilities': return 'Facilities';
        default:
            $t = preg_replace('/^edtb_/', '', $table);
            $t = str_replace('_', ' ', $t);
            $t = trim($t);
            return $t === '' ? strtoupper($table) : ucwords($t);
    }
}

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
                'format' => [
                    'needs_permit' => 'bool',
                ],
                'quick_filters' => [
                    ['label' => 'Allegiance', 'items' => [
                        ['label'=>'Federation', 'field'=>'allegiance', 'value'=>'Federation'],
                        ['label'=>'Empire',     'field'=>'allegiance', 'value'=>'Empire'],
                        ['label'=>'Independent','field'=>'allegiance', 'value'=>'Independent'],
                        ['label'=>'Alliance',   'field'=>'allegiance', 'value'=>'Alliance'],
                    ]],
                    ['label' => 'Permit', 'items' => [
                        ['label'=>'Requires Permit', 'field'=>'needs_permit', 'value'=>'1', 'icon'=>'🛂'],
                        ['label'=>'No Permit',       'field'=>'needs_permit', 'value'=>'0', 'icon'=>'✅'],
                    ]],
                    ['label' => 'Economy', 'items' => [
                        ['label'=>'High Tech', 'field'=>'economy', 'value'=>'High Tech'],
                        ['label'=>'Industrial','field'=>'economy', 'value'=>'Industrial'],
                        ['label'=>'Extraction','field'=>'economy', 'value'=>'Extraction'],
                    ]],
                ],
                'presets' => [
                    'Overview' => ['name','allegiance','government','economy','population'],
                    'Flags'    => ['name','needs_permit'],
                ],
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
                'format' => [
                    'has_blackmarket' => 'bool',
                    'has_outfitting'  => 'bool',
                    'has_shipyard'    => 'bool',
                ],
                'quick_filters' => [
                    ['label' => 'Facilities', 'items' => [
                        ['label'=>'Shipyard',   'field'=>'has_shipyard',   'value'=>'1', 'icon'=>'🚢'],
                        ['label'=>'Outfitting', 'field'=>'has_outfitting', 'value'=>'1', 'icon'=>'🛠️'],
                        ['label'=>'Black Market','field'=>'has_blackmarket','value'=>'1', 'icon'=>'💱'],
                    ]],
                    ['label' => 'Pad', 'items' => [
                        ['label'=>'Large Pad',  'field'=>'max_landing_pad_size', 'value'=>'L'],
                        ['label'=>'Medium Pad', 'field'=>'max_landing_pad_size', 'value'=>'M'],
                        ['label'=>'Small Pad',  'field'=>'max_landing_pad_size', 'value'=>'S'],
                    ]],
                ],
                'presets' => [
                    'Commerce' => ['name','type','max_landing_pad_size','has_outfitting','has_shipyard'],
                    'Security' => ['name','type','has_blackmarket'],
                ],
            ];

        case 'edtb_facilities':
            return [
                'list' => ['id','code','name'],
                'labels' => [
                    'id'   => 'Id',
                    'code' => 'Code',
                    'name' => 'Name',
                ],
                'order_by' => 'id DESC',
                'format' => [],
                'quick_filters' => [],
                'presets' => [
                    'Codes' => ['code','name'],
                ],
            ];

        default:
            return [];
    }
}

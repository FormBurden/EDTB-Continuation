<?php
declare(strict_types=1);

use EDTB\Domain\Stations\StationsRepository;

/**
 * Render the Stations section for System Information.
 * Presentation-only; uses the same class hooks as the original CSS.
 */
function renderStationsHtml(\mysqli $mysqli, int $systemId): string
{
    $html = '';

    $stationResult = StationsRepository::selectResultBySystemId($mysqli, (int)$systemId);
    if (!$stationResult) {
        return $html;
    }

    while ($st = $stationResult->fetch_object()) {
        $stationId = (int)($st->id ?? 0);
        $nameFull  = (string)($st->name ?? '');

        // Station title (inline: INARA link)
        $safeName  = htmlspecialchars($nameFull, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $hrefInara = 'https://inara.cz/galaxy-station/?search=' . rawurlencode($nameFull);
        $titleHtml = '<span class="si-station">' . $safeName . '</span>'
                   . '<span class="si-links" style="margin-left:6px; vertical-align: middle;">'
                   . '<a href="' . $hrefInara . '" target="_blank" title="INARA" class="external">INARA</a>'
                   . '</span>';

        // Meta
        $type        = (string)($st->type ?? '');
        $isPlanetary = (string)($st->is_planetary ?? '0');
        $allegiance  = (string)($st->allegiance ?? '');
        $government  = (string)($st->government ?? '');

        // Economies (primary [+ secondary])
        $primary   = (string)($st->economy ?? ($st->primary_economy ?? ''));
        $secondary = (string)($st->second_economy ?? ($st->secondary_economy ?? ''));
        $econBits  = [];
        if ($primary !== '')   { $econBits[] = htmlspecialchars($primary, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); }
        if ($secondary !== '') { $econBits[] = htmlspecialchars($secondary, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); }
        $economies = implode(' + ', $econBits);

        // Distance (Ls) — repo aliases to distance_to_arrival when needed
        $lsFromStar = (int)($st->distance_to_arrival ?? ($st->ls_from_star ?? 0));
        $lsText     = $lsFromStar > 0 ? number_format($lsFromStar) . ' Ls - ' : '';

        // Pad size text "(L|M|S)"
        $padRaw  = strtoupper(trim((string)($st->max_landing_pad_size ?? '')));
        $padText = ($padRaw === 'L' || $padRaw === 'M' || $padRaw === 'S') ? '<span class="si-pad">(' . $padRaw . ')</span>' : '';

        // Station type icon (with planetary overlay class)
        $t   = strtolower(trim($type));
        if ($t === '') { $t = 'unknown'; }
        $cls = 'sticon sticon-' . preg_replace('/[^a-z0-9_-]/', '-', $t);
        if ($isPlanetary === '1' || $isPlanetary === 'y' || $isPlanetary === 'Y' || $isPlanetary === 'true') {
            $cls .= ' sticon-planetary';
        }
        $typeIcon = '<div class="' . $cls . '"></div>';

        // Facilities row (icons) — unavailable ones are dimmed with .si-off for layout parity
                // Facilities row (icons) — use icon map and actual DB columns
        $facIconMap = require __DIR__ . '/../Lookups/FacilitiesIconMap.php'; // key => filename
        $facLabels = [
            'market'       => 'Commodities',
            'black_market' => 'Black Market',
            'outfitting'   => 'Outfitting',
            'shipyard'     => 'Shipyard',
            'refuel'       => 'Refuel',
            'repair'       => 'Repair',
            'restock'      => 'Restock',
        ];
        $facVals = [
            'market'       => (int)($st->commodities_market ?? 0),
            'black_market' => (int)($st->black_market ?? 0),
            'outfitting'   => (int)($st->outfitting ?? 0),
            'shipyard'     => (int)($st->shipyard ?? 0),
            'refuel'       => (int)($st->refuel ?? 0),
            'repair'       => (int)($st->repair ?? 0),
            'restock'      => (int)($st->rearm ?? 0), // DB uses 'rearm'
        ];

        $facHtml = '<div class="si-facilities">';
        foreach ($facIconMap as $key => $filename) {
            $on    = (int)($facVals[$key] ?? 0) === 1;
            $title = htmlspecialchars($facLabels[$key] ?? ucfirst(str_replace('_',' ',$key)), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
            $cls   = 'si-facility-img' . ($on ? '' : ' si-off');
            $src   = '/style/img/facilities/' . $filename;
            $facHtml .= '<img class="' . $cls . '" src="' . $src . '" alt="' . $title . '" title="' . $title . '">';
        }
        $facHtml .= '</div>';


        // Commodities summary line
        $hasMarket   = (int)($st->commodities_market ?? 0) === 1;
        $hasOutf     = (int)($st->outfitting ?? 0) === 1;
        $hasShipyard = (int)($st->shipyard ?? 0) === 1;

                $commodHtml = buildCommoditiesText($st);
                if ((int)$st->commodities_market === 0) { $html .= '<div>No market</div>'; }
                if (!empty($st->selling_ships)) {
                    $html .= '<div class="si-selling"><strong>Selling ships:</strong> ' .
                             htmlspecialchars((string)$st->selling_ships, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') .
                             '</div>';
                }
                if (!empty($st->selling_modules)) {
                    $html .= '<div class="si-selling-modules">' .
                             htmlspecialchars((string)$st->selling_modules, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') .
                             '</div>';
                }
        
        


        $sellingShips = trim((string)($st->selling_ships ?? ($st->ships_sold ?? '')));
        $sellingShipsHtml = $sellingShips !== ''
            ? '<div class="si-selling"><strong>Selling ships:</strong> ' . htmlspecialchars($sellingShips, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '</div>'
            : '';

        $sellingModules = trim((string)($st->selling_modules ?? ($st->modules_sold ?? '')));
        $sellingModulesHtml = $sellingModules !== ''
            ? '<div class="si-selling-modules">' . htmlspecialchars($sellingModules, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '</div>'
            : '';


        // Build card
        $html .= '<div class="systeminfo_station">';
        $html .= $typeIcon;
        $html .= '<div class="systeminfo_station_info">';
        $html .= '<div class="si-title">' . $titleHtml . ' ' . $padText . ' <span class="si-dim">' . $lsText . '</span></div>';

        $metaBits = array_filter([
            $allegiance !== '' ? htmlspecialchars($allegiance, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') : null,
            $government !== '' ? htmlspecialchars($government, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') : null,
            $economies !== '' ? $economies : null,
        ]);
        if (!empty($metaBits)) {
            $html .= '<div class="si-meta">' . implode(' &middot; ', $metaBits) . '</div>';
        }

        $html .= $facHtml . '<br>';
        $html .= $commodHtml;
        $html .= $sellingShipsHtml;
        $html .= $sellingModulesHtml;


        $html .= '</div>'; // .systeminfo_station_info
        $html .= '</div>'; // .systeminfo_station
    }

    $stationResult->close();
    return $html;
}

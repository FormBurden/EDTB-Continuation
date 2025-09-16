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
        $lsText     = $lsFromStar > 0 ? number_format($lsFromStar) . ' Ls' : '';


        // Pad size text "(L|M|S)"
        $padRaw  = strtoupper(trim((string)($st->max_landing_pad_size ?? '')));
        $padText = ($padRaw === 'L' || $padRaw === 'M' || $padRaw === 'S') ? '<span class="si-pad">(' . $padRaw . ')</span>' : '';


        // Station type icon (image-based, aligns with original UI)
        $typeIcon = getStationIcon($type, $isPlanetary, ' style="margin-right:6px"');


        // Facilities row (icons) — unavailable ones are dimmed with .si-off for layout parity
                // Facilities row (icons) — use icon map and actual DB columns
        $facIconMap = require __DIR__ . '/../Lookups/FacilitiesIconMap.php'; // key => filename
        $facLabels = [
            'market'               => 'Commodities',
            'black_market'         => 'Black Market',
            'outfitting'           => 'Outfitting',
            'shipyard'             => 'Shipyard',
            'refuel'               => 'Refuel',
            'repair'               => 'Repair',
            'restock'              => 'Restock',

            // Odyssey-era
            'pioneer_supplies'     => 'Pioneer Supplies',
            'interstellar_factors' => 'Interstellar Factors',
            'apex'                 => 'Apex Interstellar',
            'frontline'            => 'Frontline Solutions',
            'vista_genomics'       => 'Vista Genomics',
            'bartender'            => 'Bartender',
            'redemption_office'    => 'Redemption Office',
        ];

        // Helper to resolve facility presence from several possible column names
        $has = static function ($row, array $cands): int {
            foreach ($cands as $c) {
                if (isset($row->$c)) {
                    $v = $row->$c;
                    if ($v === 1 || $v === '1' || $v === true || $v === 'y' || $v === 'Y' || $v === 'true') {
                        return 1;
                    }
                }
            }
            return 0;
        };

        $facVals = [
            'market'               => (int)($st->commodities_market ?? 0),
            'black_market'         => (int)($st->black_market ?? 0),
            'outfitting'           => (int)($st->outfitting ?? 0),
            'shipyard'             => (int)($st->shipyard ?? 0),
            'refuel'               => (int)($st->refuel ?? 0),
            'repair'               => (int)($st->repair ?? 0),
            'restock'              => (int)($st->rearm ?? 0), // DB uses 'rearm'

            // Odyssey-era (aliases handled here; canonical keys above)
            'pioneer_supplies'     => $has($st, ['pioneer_supplies','pioneer']),
            'interstellar_factors' => $has($st, ['interstellar_factors','if','interstellarfactors','factors']),
            'apex'                 => $has($st, ['apex','apex_interstellar','apex_transport','apex_shuttle']),
            'frontline'            => $has($st, ['frontline','frontline_solutions']),
            'vista_genomics'       => $has($st, ['vista_genomics','genomics','vgenomics']),
            'bartender'            => $has($st, ['bartender','bar']),
            'redemption_office'    => $has($st, ['redemption_office','redeem_vouchers','redemption']),
        ];


        $facHtml = '<div class="si-facilities">';
        foreach ($facIconMap as $key => $filename) {
            $on    = (int)($facVals[$key] ?? 0) === 1;
            $title = htmlspecialchars($facLabels[$key] ?? ucfirst(str_replace('_',' ',$key)), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
            $cls   = 'si-facility-img' . ($on ? '' : ' si-off');

            $base = '/style/img/facilities/';
            $src  = $base . $filename;
            $root = rtrim($_SERVER['DOCUMENT_ROOT'] ?? '', '/');

            $exists = static function (string $p) use ($root): bool {
                $fs = $root . $p;
                return ($fs !== $root) && is_file($fs);
            };

            if (!$exists($src)) {
                $stem   = preg_replace('/\.(?:png|svg)$/i', '', $filename);
                $trySvg = $base . $stem . '.svg';
                $tryPng = $base . $stem . '.png';
                if     ($exists($trySvg)) { $src = $trySvg; }
                elseif ($exists($tryPng)) { $src = $tryPng; }
                elseif ($exists($base . 'market.svg')) { $src = $base . 'market.svg'; }
                else  { $src = $base . 'market.svg'; }
            }

            $facHtml .= '<img class="' . $cls . '" src="' . $src . '" alt="' . $title . '" title="' . $title . '">';
        }


        $facHtml .= '</div>';


                // Commodities summary line
        $hasMarket   = (int)($st->commodities_market ?? 0) === 1;
        $hasOutf     = (int)($st->outfitting ?? 0) === 1;
        $hasShipyard = (int)($st->shipyard ?? 0) === 1;

        // Show either the import/export/prohibited bundle or a compact "No market"
        $commodHtml = $hasMarket ? buildCommoditiesText($st) : '<div>No market</div>';

        // Selling (kept separate for consistent placement under facilities/commodities)
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
        $html .= '<div class="si-title">' . $titleHtml . ($padText !== '' ? ' ' . $padText : '') . ($lsText !== '' ? ' <span class="si-dim">' . $lsText . '</span>' : '') . '</div>';

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

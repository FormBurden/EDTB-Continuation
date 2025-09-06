<?php
declare(strict_types=1);

/**
 * System Information page formatters extracted from System/getData_systemInfo.php
 * Keep these helpers presentation-only; no DB or global side effects here.
 */
/**
 * Build the facilities array from a station object.
 * Keys match icon filenames in /style/img/facilities/.
 */
function facilitiesFromStation(object $stationObj): array
{
    return [
        'shipyard'     => (int)$stationObj->shipyard,
        'outfitting'   => (int)$stationObj->outfitting,
        'market'       => (int)$stationObj->commodities_market,
        'black_market' => (int)$stationObj->black_market,
        'refuel'       => (int)$stationObj->refuel,
        'repair'       => (int)$stationObj->repair,
        'restock'      => (int)$stationObj->rearm,
    ];
}

/**
 * Return the facilities icon strip HTML for a station.
 */
function buildFacilitiesHtml(array $facilities): string
{
    // Load icon map (filenames only) and build the row of icons for “true” facilities.
    $iconMap = require __DIR__ . '/../Lookups/FacilitiesIconMap.php';

    $html = [];
    foreach ($facilities as $key => $enabled) {
        if (!$enabled) {
            continue;
        }
        $file = $iconMap[$key]; // no guards — mapping must be complete
        $alt  = ucfirst(str_replace('_', ' ', $key));
        $html[] = '<img src="/style/img/facilities/' . $file . '" class="icon" alt="' . $alt . '">';
    }

    return implode("\n", $html);
}


/**
 * Build the bracketed "State / Security / Visits" segment for the header.
 */
function formatSiHeaderMeta($state, $security, int $numVisits): string
{
    $__parts = [];

    if (!empty($state) && $state !== 'None') {
        $__parts[] = 'State: ' . $state;
    }
    if (!empty($security) && $security !== 'None') {
        $__parts[] = 'Security: ' . $security;
    }
    if ($numVisits > 0) {
        $__parts[] = 'Visits: ' . $numVisits;
    }

    return count($__parts) ? '[ ' . implode(' - ', $__parts) . ' ]' : '';
}

/**
 * Build the clickable station title with an inline Wikipedia trigger.
 */
function buildStationTitleWithWiki(int $stationId, string $title): string
{
    $wikiQuery = $title;

    $out  = '<span class="wp" onclick="get_wikipedia(\'' . addslashes($wikiQuery) . '\', \'' . $stationId . '\')">';
    $out .= '<a href="javascript:void(0)" title="Ask Wikipedia about ' . htmlspecialchars($wikiQuery, ENT_QUOTES) . '" style="font-weight: inherit">'
         .  htmlspecialchars($title, ENT_QUOTES)
         .  '</a></span>';

    return $out;
}

/**
 * Build the system crosslinks strip and add the "Map this system" link if not mapped.
 */
function buildSystemCrosslinks(string $systemName): string
{
    $out = \EDTB\source\System::crosslinks($systemName);

    if (!\EDTB\source\System::isMapped($systemName)) {
        $out .= '<a href="/SystemMap/?system=' . urlencode($systemName) . '" style="color: inherit" title="Map this system">';
        $out .= '<img src="/style/img/grid_g.png" class="icon" style="margin-left: 5px; margin-right: 0">';
        $out .= '</a>';
    }

    return $out;
}

/**
 * Build the little "[ Nearby rares within X ly: N ]" toggle label + hidden list.
 */
function buildRaresMiniLabel(int $actualNumRes, $rareRange, string $cRaresData, $x, $y, $z): string
{
    if ($actualNumRes > 0 && validCoordinates($x, $y, $z)) {
        $out  = '&nbsp;&nbsp;<span onclick="$(\'#rares\').fadeToggle(\'fast\')">';
        $out .= '<a href="javascript:void(0)" title="Click to toggle rares list">[ Nearby rares within ' . $rareRange . ' ly: ' . $actualNumRes . ' ]</a>';
        $out .= $cRaresData . '</span>';
        return $out;
    }
    return '';
}

/**
 * Build the three commodity lines (Import / Export / Prohibited) for a station.
 * (Safe to include even if not used yet.)
 */
function buildCommodityLines($stationObj): string
{
    $import = empty($stationObj->import_commodities)
        ? ''
        : '<strong>Import commodities:</strong> ' . $stationObj->import_commodities . '<br>';

    $export = empty($stationObj->export_commodities)
        ? ''
        : '<strong>Export commodities:</strong> ' . $stationObj->export_commodities . '<br>';

    $prohibited = empty($stationObj->prohibited_commodities)
        ? ''
        : '<strong>Prohibited commodities:</strong> ' . $stationObj->prohibited_commodities . '<br>';

    return $import . $export . $prohibited;
}

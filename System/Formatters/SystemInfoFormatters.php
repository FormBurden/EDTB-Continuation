<?php
declare(strict_types=1);

/**
 * System Information page formatters extracted from System/getData_systemInfo.php
 * Presentation-only helpers (no DB access).
 */

/**
 * Format the mini meta after the system name: [ STATE - SECURITY - Visits:N ]
 */
function formatSiHeaderMeta(string $state, string $security, int $numVisits): string
{
    $state    = trim($state) === '' ? 'Unknown' : strtoupper($state);
    $security = trim($security) === '' ? 'Unknown' : strtoupper($security);
    return '[ ' . $state . ' - ' . $security . ' - Visits: ' . (int)$numVisits . ' ]';
}

/**
 * Build a simple station title; if you later want a wiki/external link, wire it here.
 */
function buildStationTitleWithWiki(int $stationId, string $fullTitle): string
{
    // Keep plain text title for now (legacy sometimes linked to local station page)
    return htmlspecialchars($fullTitle, ENT_QUOTES, 'UTF-8');
}

/**
 * Combine primary/secondary economies into a display string like "Industrial / Military".
 */
function buildEconomiesText(object $stationObj): string
{
    $primary   = trim((string)($stationObj->economies ?? ''));
    $secondary = trim((string)($stationObj->secondary_economy ?? ''));
    if ($primary !== '' && $secondary !== '' && strcasecmp($primary, $secondary) !== 0) {
        return $primary . ' / ' . $secondary;
    }
    return $primary !== '' ? $primary : $secondary;
}

/**
 * Facilities strip: expects an associative array of booleans keyed by facility name.
 * Uses /style/img/facilities/{key}.png icon filenames.
 */
function buildFacilitiesHtml(array $facilities): string
{
    // Map facility key -> icon filename (fallback: {key}.png)
    $iconMapPath = __DIR__ . '/../Lookups/FacilitiesIconMap.php';
    $iconMap = file_exists($iconMapPath) ? require $iconMapPath : [];

    $html = [];
    foreach ($facilities as $key => $enabled) {
        if (!$enabled) {
            continue;
        }
        $file = $iconMap[$key] ?? ($key . '.png');
        $alt  = ucfirst(str_replace('_',' ', (string)$key));
        $html[] = '<img src="/style/img/facilities/' . htmlspecialchars($file) . '" class="icon" title="' . htmlspecialchars($alt) . '" alt="' . htmlspecialchars($alt) . '">';
    }

    return $html ? '<span class="facilities">' . implode('&nbsp;', $html) . '</span>' : '';
}

/**
 * Human-readable allowed ships / landing pad sizes text.
 */
function buildPadSizeText(string $maxLandingPadSize): string
{
    $s = strtoupper($maxLandingPadSize);
    if ($s === 'L' || $s === 'LARGE') {
        return 'Large pads';
    }
    if ($s === 'M' || $s === 'MEDIUM') {
        return 'Medium pads';
    }
    if ($s === 'S' || $s === 'SMALL') {
        return 'Small pads';
    }
    return '';
}

/**
 * Import/Export/Prohibited summary lines.
 */
function buildCommoditiesText(object $stationObj): string
{
    $import = trim((string)($stationObj->import_commodities ?? ''));
    $export = trim((string)($stationObj->export_commodities ?? ''));
    $proh   = trim((string)($stationObj->prohibited_commodities ?? ''));

    $out = '';
    if ($import !== '') {
        $out .= '<strong>Import commodities:</strong> ' . htmlspecialchars($import) . '<br>';
    }
    if ($export !== '') {
        $out .= '<strong>Export commodities:</strong> ' . htmlspecialchars($export) . '<br>';
    }
    if ($proh !== '') {
        $out .= '<strong>Prohibited commodities:</strong> ' . htmlspecialchars($proh) . '<br>';
    }
    return $out;
}

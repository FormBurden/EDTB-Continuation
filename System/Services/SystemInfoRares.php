<?php
declare(strict_types=1);

use EDTB\Domain\Rares\RaresRepository;

/**
 * Fetch nearby rares as a mysqli_result and a row count, mirroring legacy behavior.
 * Returns [mysqli_result|false, int $raresCloseby]
 */
function fetchNearbyRares(
    \mysqli $mysqli,
    string $systemName,
    float $cx,
    float $cy,
    float $cz,
    $rangeRaw
) {
    // Disabled or nonsensical range — match legacy outcome: no rares.
    if (isset($rangeRaw) && (string)$rangeRaw === '-1') {
        return [false, 0];
    }
    $range = (float)$rangeRaw;
    if ($range <= 0) {
        $range = 50.0;
    }

    // Attempt the legacy-style query (repository already checks table existence).
    $res = RaresRepository::selectNearbyRaresResult($mysqli, $systemName, $cx, $cy, $cz, $range);
    $count = 0;
    if ($res instanceof \mysqli_result) {
        $count = $res->num_rows;
    }
    return [$res, $count];
}

/**
 * Render the Nearby Rares panel and return: [html, actualNumRes, miniLabel]
 */
function renderNearbyRaresHtml(
    \mysqli $mysqli,
    string $systemName,
    array $curSys,
    array $settings,
    $res,
    int $actualNumRes
): array {
    $cRaresData = '<div class="si-rares">';

    if (!($res instanceof \mysqli_result) || $actualNumRes === 0) {
        $cRaresData .= '<div class="light">No nearby rares found</div>';
        $cRaresData .= '</div>';
        // Preserve the mini-label call below with zero results
        $rareText = buildRaresMiniLabel(0, ($settings['rare_range'] ?? null), $cRaresData,
                                        ($curSys['x'] ?? null), ($curSys['y'] ?? null), ($curSys['z'] ?? null));
        return [$cRaresData, 0, $rareText];
    }

    while ($row = $res->fetch_object()) {
        $rareName  = (string)($row->rare ?? ($row->rare_name ?? $row->name ?? 'Rare Commodity'));
        $sysName   = (string)($row->system_name ?? $row->__joined_system_name ?? '');
        $stName    = (string)($row->station ?? $row->station_name ?? $row->market ?? '');
        $distance  = (float)($row->distance ?? 0.0);
        $price     = isset($row->price) ? (float)$row->price : null;

        $rareSafe = htmlspecialchars($rareName, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $sysSafe  = htmlspecialchars($sysName, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $stSafe   = $stName !== '' ? htmlspecialchars($stName, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') : '';

        $inaraRare = 'https://inara.cz/galaxy-commodities/?search=' . rawurlencode($rareName);
        $inaraSys  = 'https://inara.cz/galaxy-system/?search=' . rawurlencode($sysName);

        $cRaresData .= '<div class="si-rare">';
        $cRaresData .= '<div class="si-rare-title">'
                     . '<span class="si-rare-name"><a href="' . $inaraRare . '" target="_blank" class="external">' . $rareSafe . '</a></span>'
                     . ' <span class="si-dim">(' . number_format($distance, 1) . ' Ly)</span>'
                     . '</div>';
        $cRaresData .= '<div class="si-rare-meta">'
                     . '<a href="' . $inaraSys . '" target="_blank" class="external">' . $sysSafe . '</a>'
                     . ($stSafe !== '' ? ' &middot; ' . $stSafe : '')
                     . ($price !== null ? ' &middot; ' . number_format($price) . ' cr' : '')
                     . '</div>';
        $cRaresData .= '</div>';
    }

    $cRaresData .= '</div>';

    // Mini-label exactly as before (delegated to formatter)
    $rareText = buildRaresMiniLabel(
        (int)$actualNumRes,
        ($settings['rare_range'] ?? null),
        $cRaresData,
        ($curSys['x'] ?? null),
        ($curSys['y'] ?? null),
        ($curSys['z'] ?? null)
    );

    return [$cRaresData, $actualNumRes, $rareText];
}
/**
 * Legacy shim for older call-sites.
 * Returns [html, actualNumRes, miniLabel] just like before.
 */
function renderRaresBlock(
    \mysqli $mysqli,
    string $systemName,
    array $curSys,
    array $settings
): array {
    $cx = (float)($curSys['x'] ?? 0.0);
    $cy = (float)($curSys['y'] ?? 0.0);
    $cz = (float)($curSys['z'] ?? 0.0);
    $rangeRaw = $settings['rare_range'] ?? 50.0;

    // Reuse the new pipeline
    [$res, $count] = fetchNearbyRares($mysqli, $systemName, $cx, $cy, $cz, $rangeRaw);
    return renderNearbyRaresHtml($mysqli, $systemName, $curSys, $settings, $res, (int)$count);
}


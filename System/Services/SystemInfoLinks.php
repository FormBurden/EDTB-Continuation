<?php
declare(strict_types=1);

/**
 * Build the external crosslinks (INARA/EDDB/EDSM/etc.) and the short user distances label.
 * 1:1 with legacy output — no behavior guards added.
 */
function buildSystemLinksAndDists(
    string $systemName,
    array $curSys,
    array $settings
): array {
    $links = '';

    // INARA (station/system) — legacy uses system page
    $links .= '<a href="https://inara.cz/galaxy-system/?search=' . rawurlencode($systemName) . '" target="_blank" title="INARA">';
    $links .= '<img src="/style/img/inara.png" class="extlink" alt="INARA">';
    $links .= '</a>';

    // EDDB
    $links .= '&nbsp;<a href="https://eddb.io/system?systemName=' . rawurlencode($systemName) . '" target="_blank" title="EDDB">';
    $links .= '<img src="/style/img/eddb.png" class="extlink" alt="EDDB">';
    $links .= '</a>';

    // EDSM
    $links .= '&nbsp;<a href="https://www.edsm.net/en/system?systemName=' . rawurlencode($systemName) . '" target="_blank" title="EDSM">';
    $links .= '<img src="/style/img/edsm.png" class="extlink" alt="EDSM">';
    $links .= '</a>';

    // Coriolis link is usually build/ship specific; legacy sometimes includes a generic link or omits.
    // If your current header already prints Coriolis, mirror it here exactly. Otherwise, leave out.

    // User distances short label
    // (If your legacy code computes multiple distances, mirror that logic; here we preserve the simple case.)
    $userDists = '';
    if (isset($curSys['x'], $curSys['y'], $curSys['z'])) {
        // Keep the exact formatting you used before (placeholder kept simple).
        // If legacy used a specific formatter, we’ll swap this to call it when we wire the controller.
        $userDists = '';
    }

    return [$links, $userDists];
}

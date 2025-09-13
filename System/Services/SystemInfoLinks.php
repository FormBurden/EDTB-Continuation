<?php
declare(strict_types=1);

/**
 * Build the external crosslinks (INARA/EDDB/EDSM/etc.) and the short user distances label.
 * Returns [$linksHtml, $userDistsHtml].
 * 1:1 simple icon strip with small spacing.
 */
function buildSystemLinksAndDists(
    string $systemName,
    array $curSys,
    array $settings
): array {
    $q = rawurlencode($systemName);

    // External sites (icon strip)
    $links  = '';
    $links .= '<span class="si-links" style="vertical-align: middle; margin-left: 6px;">';

    // INARA
    $links .= '<a href="https://inara.cz/galaxy-system/?search=' . $q . '" target="_blank" title="INARA">';
    $links .= '<img src="/style/img/external_link.png" class="extlink" alt="INARA">';
    $links .= '</a>';

    // EDDB
    $links .= '&nbsp;<a href="https://eddb.io/system?systemName=' . $q . '" target="_blank" title="EDDB">';
    $links .= '<img src="/style/img/eddb.png" class="extlink" alt="EDDB">';
    $links .= '</a>';

    // EDSM
    $links .= '&nbsp;<a href="https://www.edsm.net/en/system?systemName=' . $q . '" target="_blank" title="EDSM">';
    $links .= '<img src="/style/img/external_link.png" class="extlink" alt="EDSM">';
    $links .= '</a>';

    // Map this system (internal)
    $links .= '&nbsp;<a href="/SystemMap/?system=' . urlencode($systemName) . '" style="color: inherit" title="Map this system">';
    $links .= '<img src="/style/img/grid_g.png" class="icon" style="margin-left: 5px; margin-right: 0" alt="Map">';
    $links .= '</a>';

    $links .= '</span>';

    // User distances short label (header will append if non-empty)
    $userDists = '';

    return [$links, $userDists];
}

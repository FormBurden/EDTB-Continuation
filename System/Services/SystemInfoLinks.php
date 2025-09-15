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
    $links  = '<span class="si-links" style="vertical-align: middle; margin-left: 6px;">';

    // INARA
    $links .= '<a href="https://inara.cz/galaxy-system/?search=' . $q . '" target="_blank" rel="noopener" title="INARA">';
    $links .= '<img src="/style/img/external_link.png" class="extlink" alt="INARA">';
    $links .= '</a>&nbsp;';

    // EDDB
    $links .= '<a href="https://eddb.io/system?systemName=' . $q . '" target="_blank" rel="noopener" title="EDDB">';
    $links .= '<img src="/style/img/eddb.png" class="extlink" alt="EDDB">';
    $links .= '</a>&nbsp;';

    // EDSM
    $links .= '<a href="https://www.edsm.net/en/system?systemName=' . $q . '" target="_blank" rel="noopener" title="EDSM">';
    $links .= '<img src="/style/img/external_link.png" class="extlink" alt="EDSM">';
    $links .= '</a>';

    // Map this system (internal)
    $links .= '&nbsp;<a href="/SystemMap/?system=' . urlencode($systemName) . '" style="color: inherit" title="Map this system">';
    $links .= '<img src="/style/img/grid_g.png" class="icon" style="margin-left: 5px; margin-right: 0" alt="Map">';
    $links .= '</a>';

    $links .= '</span>';

    // User distances short label (header will append if non-empty)
    $userDists = buildUserDistsShortLabel($systemName, $curSys, $settings);

    return [$links, $userDists];
}

/**
 * Build the short right-aligned user distance label, or return empty string.
 * Non-fatal: if we can't compute, we just omit it.
 */
function buildUserDistsShortLabel(string $systemName, array $curSys, array $settings): string
{
    // 1) If caller already provided a precomputed distance
    foreach (['dist_to_cur', 'distance_to_current', 'distance_current'] as $k) {
        if (isset($curSys[$k]) && is_numeric($curSys[$k])) {
            $d = (float)$curSys[$k];
            if ($d > 0) {
                return '<span class="right" style="font-size: 11px">Current: ' . number_format($d, 1) . ' ly</span>';
            }
        }
    }

    // 2) If both positions exist, compute Euclidean distance
    $sx = $curSys['x'] ?? null;
    $sy = $curSys['y'] ?? null;
    $sz = $curSys['z'] ?? null;

    $ux = $settings['user_x'] ?? $settings['cur_x'] ?? $settings['current_x'] ?? null;
    $uy = $settings['user_y'] ?? $settings['cur_y'] ?? $settings['current_y'] ?? null;
    $uz = $settings['user_z'] ?? $settings['cur_z'] ?? $settings['current_z'] ?? null;

    if (is_numeric($sx) && is_numeric($sy) && is_numeric($sz)
        && is_numeric($ux) && is_numeric($uy) && is_numeric($uz)) {
        $dx = (float)$sx - (float)$ux;
        $dy = (float)$sy - (float)$uy;
        $dz = (float)$sz - (float)$uz;
        $d  = sqrt($dx*$dx + $dy*$dy + $dz*$dz);
        if ($d >= 0.05) {
            return '<span class="right" style="font-size: 11px">Current: ' . number_format($d, 1) . ' ly</span>';
        }
        return '<span class="right" style="font-size: 11px">Current: 0.0 ly</span>';
    }

    // 3) Unknown — omit
    return '';
}

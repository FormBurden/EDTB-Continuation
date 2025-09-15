<?php
declare(strict_types=1);

/**
 * Rares rendering helpers for System Information.
 * Provides:
 *  - renderRaresBlock($rareResult, int $raresCloseby, array $settings, array $curSys): array
 *  - fetchNearbyRares(mysqli $mysqli, string $systemName, float $cx, float $cy, float $cz, $range = 50.0): array
 *  - renderNearbyRaresHtml(string $systemName, array $curSys, array $settings, $rareResult, int $raresCloseby): array
 */

/**
 * Iterate over a mysqli_result or any Traversable/array as objects.
 *
 * @param mixed $res
 * @return \Generator
 */
function _si_iter_rows($res): \Generator
{
    if ($res instanceof \mysqli_result) {
        while ($o = $res->fetch_object()) {
            yield $o;
        }
        return;
    }
    if (is_array($res)) {
        foreach ($res as $o) {
            yield is_object($o) ? $o : (object)$o;
        }
        return;
    }
    if ($res instanceof \Traversable) {
        foreach ($res as $o) {
            yield is_object($o) ? $o : (object)$o;
        }
    }
}

/**
 * Render the rares HTML block and compute the mini-label.
 * Returns [string $cRaresData, int $actualNumRes, string $rareText]
 *
 * Expected $rareResult rows to have fields:
 *  item, price, distance, system_name, station, ls_to_star, sc_est_mins, needs_permit, max_landing_pad_size
 */
function renderRaresBlock($rareResult, int $raresCloseby, array $settings, array $curSys): array
{
    $cRaresData   = '<div class=\"si-rares\">';
    $actualNumRes = 0;

    $range = $settings['rare_range'] ?? 50.0;
    $range = is_numeric($range) ? (float)$range : 50.0;

    if ($raresCloseby > 0 && $rareResult) {
        foreach (_si_iter_rows($rareResult) as $rareObj) {
            $distance = (float)($rareObj->distance ?? 0.0);
            if ($distance > $range) {
                continue; // respect range filter
            }

            $item  = htmlspecialchars((string)($rareObj->item ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
            $price = (isset($rareObj->price) && is_numeric($rareObj->price)) ? number_format((float)$rareObj->price) : '';
            $sys   = htmlspecialchars((string)($rareObj->system_name ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
            $stn   = htmlspecialchars((string)($rareObj->station ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');

            $ls    = (isset($rareObj->ls_to_star) && is_numeric($rareObj->ls_to_star)) ? number_format((float)$rareObj->ls_to_star) . ' ls' : '';
            $mins  = (isset($rareObj->sc_est_mins) && is_numeric($rareObj->sc_est_mins)) ? number_format((float)$rareObj->sc_est_mins) . ' min' : '';
            $permit= (isset($rareObj->needs_permit) && ((string)$rareObj->needs_permit === '1')) ? 'Permit needed' : '';
            $pad   = htmlspecialchars((string)($rareObj->max_landing_pad_size ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');

            $cRaresData .= '<div class=\"rare\">';
            $cRaresData .= '[' . number_format($distance, 1) . ' ly] ' . $item;
            if ($price !== '') {
                $cRaresData .= ' (' . $price . ' CR)';
            }
            $cRaresData .= '<br>' . $sys . ' - ' . $stn;

            $extra = [];
            if ($ls !== '')     { $extra[] = $ls; }
            if ($mins !== '')   { $extra[] = '(' . $mins . ')'; }
            if ($permit !== '') { $extra[] = $permit; }
            if ($pad !== '')    { $extra[] = $pad; }

            if ($extra) {
                $cRaresData .= ' — ' . implode(' ', $extra);
            }

            $cRaresData .= '</div>';
            $actualNumRes++;
        }
    } else {
        $cRaresData .= '<div class=\"rare rare-empty\">No rares nearby</div>';
    }

    $cRaresData .= '</div>';

    // Mini-label (delegate to formatter if present)
    if (function_exists('buildRaresMiniLabel')) {
        $rareText = buildRaresMiniLabel(
            (int)$actualNumRes,
            $range,
            $cRaresData,
            ($curSys['x'] ?? null),
            ($curSys['y'] ?? null),
            ($curSys['z'] ?? null)
        );
    } else {
        $rareText = $actualNumRes > 0 ? ('Rares: ' . $actualNumRes . ' within ' . number_format($range, 0) . ' ly') : '';
    }

    return [$cRaresData, $actualNumRes, $rareText];
}

/**
 * Thin wrapper expected by System/getData_systemInfo.php.
 * Returns [mysqli_result|false $result, int $countCloseby].
 */
if (!function_exists('fetchNearbyRares')) {
    function fetchNearbyRares(
        \mysqli $mysqli,
        string $systemName,
        float $cx,
        float $cy,
        float $cz,
        $range = 50.0
    ): array {
        if ($systemName === '') {
            return [false, 0];
        }
        $rng = is_numeric($range) ? (float)$range : 50.0;

        if (class_exists('\\EDTB\\Domain\\Rares\\RaresRepository')
            && method_exists('\\EDTB\\Domain\\Rares\\RaresRepository', 'selectNearbyRaresResult')) {
            $res = \EDTB\Domain\Rares\RaresRepository::selectNearbyRaresResult(
                $mysqli,
                $systemName,
                $cx, $cy, $cz,
                $rng
            );
        } else {
            $res = false;
        }

        $count = ($res instanceof \mysqli_result) ? (int)$res->num_rows : 0;
        return [$res, $count];
    }
}

/**
 * Renderer expected by System/getData_systemInfo.php.
 * Returns [$cRaresDataHtml, $actualNumRes, $rareTextMiniLabel].
 */
if (!function_exists('renderNearbyRaresHtml')) {
    function renderNearbyRaresHtml(
        string $systemName,
        array $curSys,
        array $settings,
        $rareResult,
        int $raresCloseby
    ): array {
        if (function_exists('renderRaresBlock')) {
            return renderRaresBlock($rareResult, $raresCloseby, $settings, $curSys);
        }
        $html = '<div class=\"si-rares\"><div class=\"rare rare-empty\">No rares nearby</div></div>';
        return [$html, 0, ''];
    }
}

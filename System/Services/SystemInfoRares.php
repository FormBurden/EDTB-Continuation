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
): array {
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
    $count = $res ? $res->num_rows : 0;

    return [$res, $count];
}

/**
 * Render the rares HTML block and compute the mini-label.
 * Returns [string $cRaresData, int $actualNumRes, string $rareText]
 */
function renderRaresBlock($rareResult, int $raresCloseby, array $settings, array $curSys): array
{
    $cRaresData   = '<div class="raresinfo" id="rares">';
    $actualNumRes = 0;

    if ($raresCloseby > 0 && $rareResult) {
        while ($rareObj = $rareResult->fetch_object()) {
            // keep the exact range check the page used
            if ($rareObj->distance <= ($settings['rare_range'] ?? 50.0)) {
                $cRaresData .= '[';
                $cRaresData .= number_format($rareObj->distance, 1);
                $cRaresData .= '&nbsp;ly]&nbsp';
                $cRaresData .= $rareObj->item;
                $cRaresData .= '&nbsp;(';
                $cRaresData .= number_format($rareObj->price);
                $cRaresData .= '&nbsp;CR)';
                $cRaresData .= "<br><span style='font-weight:400'>";
                $cRaresData .= "<a href='/System?system_name=" . urlencode($rareObj->system_name) . "'>";
                $cRaresData .= $rareObj->system_name;
                $cRaresData .= '</a>&nbsp;-&nbsp;';
                $cRaresData .= $rareObj->station;
                $cRaresData .= '&nbsp;(';
                $cRaresData .= $rareObj->x . ', ' . $rareObj->y . ', ' . $rareObj->z;
                $cRaresData .= ')&nbsp;-&nbsp';
                $cRaresData .= number_format($rareObj->ls_to_star);
                $cRaresData .= '&nbsp;ls&nbsp';
                $cRaresData .= '(';
                $cRaresData .= $rareObj->sc_est_mins;
                $cRaresData .= '&nbsp;min)&nbsp';
                $cRaresData .= ($rareObj->needs_permit == '1') ? '' : '&nbsp;-&nbsp;Permit needed';
                $cRaresData .= '-&nbsp';
                $cRaresData .= $rareObj->max_landing_pad_size;
                $cRaresData .= '</span><br><br>';
                $actualNumRes++;
            }
        }

        // mirror legacy close
        $rareResult->close();
    } else {
        $cRaresData .= 'No rares nearby';
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

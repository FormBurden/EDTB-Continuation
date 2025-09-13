<?php
declare(strict_types=1);

use EDTB\Domain\Stations\StationsRepository;

/**
 * Render the Stations section for System Information.
 * Presentation-only; mirrors legacy classes for CSS parity.
 */
function renderStationsHtml(\mysqli $mysqli, int $systemId): string
{
    $html = '';

    $stationResult = StationsRepository::selectResultBySystemId($mysqli, (int)$systemId);
    if (!$stationResult) {
        return $html;
    }

    if ((int)$stationResult->num_rows === 0) {
        $stationResult->close();
        return $html;
    }

    while ($st = $stationResult->fetch_object()) {
        $stationId = (int)($st->id ?? 0);
        $sNameFull = (string)($st->name ?? '');
        $sName     = buildStationTitleWithWiki($stationId, $sNameFull);

        $type        = (string)($st->type ?? '');
        $isPlanetary = (string)($st->is_planetary ?? '0');
        $allegiance  = (string)($st->allegiance ?? '');
        $government  = (string)($st->government ?? '');
        $economies   = buildEconomiesText($st);

        $lsFromStar  = (int)($st->ls_from_star ?? 0);
        $lsText      = $lsFromStar > 0 ? number_format($lsFromStar) . ' Ls - ' : '';
        $padText     = buildPadSizeText((string)($st->max_landing_pad_size ?? ''));

        // Icons
        $iconStation = getStationIcon($type, $isPlanetary, 'margin-right: 6px; vertical-align: -3px;');
        $allegIcon   = getAllegianceIcon($allegiance);

        // Station card
        $html .= '<div class="systeminfo_station" style="background-image: url(/style/img/allegiance/' . htmlspecialchars($allegIcon) . '); background-repeat: no-repeat; background-position: right 0 bottom -2px">';

        // Header row
        $html .= '<div class="heading">';
        $html .= $iconStation . $sName;
        $html .= '<span style="font-weight: 400; font-size: 10px">&nbsp;[ ' . htmlspecialchars($type) . ' - ' . htmlspecialchars($allegiance) . ' - ' . htmlspecialchars($government) . ' - ' . htmlspecialchars($economies) . ' ]</span>';
        $html .= '<span class="right"><span style="color: #ccc; font-size: 10px">' . $lsText . $padText . '</span></span>';
        $html .= '</div>';

        // Body
        $html .= '<div class="systeminfo_station_info">';

        // Facilities
        $facilities = [
            'shipyard'     => (int)($st->shipyard ?? 0),
            'outfitting'   => (int)($st->outfitting ?? 0),
            'market'       => (int)($st->market ?? 0),
            'black_market' => (int)($st->black_market ?? 0),
            'refuel'       => (int)($st->refuel ?? 0),
            'repair'       => (int)($st->repair ?? 0),
            'restock'      => (int)($st->restock ?? 0),
            'contacts'     => (int)($st->contacts ?? 0),
            'vista_genomics' => (int)($st->vista_genomics ?? 0),
            'universal_cartographics' => (int)($st->universal_cartographics ?? 0),
            'interstellar_factors' => (int)($st->interstellar_factors ?? 0),
        ];
        $html .= buildFacilitiesHtml($facilities);
        $html .= '<br>';

        // Commodities summary
        $html .= buildCommoditiesText($st);

        $html .= '</div>'; // .systeminfo_station_info
        $html .= '</div>'; // .systeminfo_station
    }

    $stationResult->close();
    return $html;
}

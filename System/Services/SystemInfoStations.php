<?php
declare(strict_types=1);

use EDTB\Domain\Stations\StationsRepository;

/**
 * Render the Stations section for System Information.
 * Presentation-only: no global side effects. Mirrors legacy markup as closely as possible.
 */
function renderStationsHtml(\mysqli $mysqli, int $systemId): string
{
    $html = '';

    $stationResult = StationsRepository::selectResultBySystemId($mysqli, (int)$systemId);

    $stationExists = $stationResult->num_rows;
    if ($stationExists == 0) {
        return 'No station data available';
    }

    while ($stationObj = $stationResult->fetch_object()) {
        $fullTitle = trim((string)$stationObj->name);
        $stationId = (int)$stationObj->id;
        $sName = buildStationTitleWithWiki($stationId, $fullTitle);

        $lsFromStar = (int)$stationObj->ls_from_star;
        $maxLandingPadSize = (string)$stationObj->max_landing_pad_size;

        $sFaction = empty($stationObj->faction) ? '' : '<strong>Faction:</strong> ' . $stationObj->faction;
        $sDistanceFromStar = $lsFromStar == 0 ? '' : number_format($lsFromStar) . ' ls - ';
        $sInformation = '<span style="float: right;  margin-right: 7px">' . $sDistanceFromStar . 'Landing pad: ' . $maxLandingPadSize . '</span><br>';
        $sGovernment = empty($stationObj->government) ? 'Government unknown' : $stationObj->government;
        $sAllegiance = empty($stationObj->allegiance) ? 'Allegiance unknown' : $stationObj->allegiance;

        $sState = empty($stationObj->state) ? '' : '<strong>State:</strong> ' . $stationObj->state . '<br>';
        $type = empty($stationObj->type) ? 'Type unknown' : $stationObj->type;
        $economies = empty($stationObj->economies) ? 'Economies unknown' : $stationObj->economies;
        $economies = empty($economies) ? 'Economies unknown' : $economies;

        $shipyard = (int)$stationObj->shipyard;
        $outfitting = (int)$stationObj->outfitting;
        $commoditiesMarket = (int)$stationObj->commodities_market;
        $blackMarket = (int)$stationObj->black_market;
        $refuel = (int)$stationObj->refuel;
        $repair = (int)$stationObj->repair;
        $rearm = (int)$stationObj->rearm;
        $isPlanetary = (int)$stationObj->is_planetary;

        $icon = getStationIcon($type, $isPlanetary);

        $facilities = facilitiesFromStation($stationObj);
        $services = buildFacilitiesHtml($facilities, (int)$stationId);

        $commodityLines = buildCommodityLines($stationObj);
        $info = $sFaction . $sInformation . $commodityLines;
        $info = str_replace("['", '', (string)$info);
        $info = str_replace(["']", "', '"], ['', ', '], (string)$info);

        $economies = str_replace("['", '', (string)$economies);
        $economies = str_replace(["']", "', '"], ['', ', '], (string)$economies);

        // allegiance image background
        $allegianceIcon = getAllegianceIcon($sAllegiance);

        $html .= '<div class="systeminfo_station" style="background-image: url(/style/img/' . $allegianceIcon . '); background-repeat: no-repeat; background-position: right 0 bottom -2px">';

        // header
        $html .= '<div class="heading">';
        $html .= $icon . $sName;

        $html .= '<span style="font-weight: 400; font-size: 10px">';
        $html .= '&nbsp;[ ' . $type . ' - ' . $sAllegiance . ' - ' . $sGovernment . ' - ' . $economies . ' ]';
        $html .= '</span>';

        $html .= '<span class="right">';
        $html .= '<a href="https://inara.cz/galaxy-station/?search=' . rawurlencode($fullTitle . ' [' . $type . ']') . '" title="View station on INARA.cz" target="_blank">';
        $html .= '<img src="/style/img/eddb.png" alt="EDDB" style="width: 10px; height: 12px">';
        $html .= '</a>';
        $html .= '</span>';

        $html .= '</div>';

        $html .= '<div class="holder">';
        $html .= '<table class="si_table_2" style="width: 100%">';
        $html .= '<tr>';
        $html .= '<td class="dark" style="width: 200px">';

        $html .= '<span style="font-size: 11px">';
        $html .= $sState;
        $html .= '</span>';

        $html .= '<table>';
        $html .= '<tr><td class="transparent">' . $services . '</td></tr>';
        $html .= '</table>';

        $html .= '</td>';

        $html .= '<td class="light" style="padding: 5px">';
        $html .= '<span style="font-size: 11px">';
        $html .= $info;
        $html .= '</span>';
        $html .= '</td>';
        $html .= '</tr>';
        $html .= '</table>';

        // Modules and shipyard/outfitting blocks:
        $sellingModules = '';
        if (!empty($stationObj->selling_modules)) {
            $modulesS = explode('-', (string)$stationObj->selling_modules);

            $modCat = StationsRepository::modulesByIds($mysqli, $modulesS);
            arsort($modCat);

            $modulesT = '';
            $modulesT .= '<table style="margin-top: 10px">';
            $modulesT .= '<tr>';
            $modulesT .= '<td class="transparent" colspan="3" style="font-weight: 700">Modules for sale</td>';
            $modulesT .= '</tr>';

            $modulesT .= '<tr style="vertical-align: top">';
            foreach ($modCat as $mCategoryName => $value) {
                $modulesT .= '<td>';
                $modulesT .= '<table style="margin-right: 10px">';
                $modulesT .= '<tr>';
                $modulesT .= '<td class="heading" colspan="3">';
                $modulesT .= $mCategoryName;
                $modulesT .= '</td>';
                $modulesT .= '</tr>';

                asort($value);

                foreach ($value as $module) {
                    $mName = $module['group_name'];
                    $mClass = $module['class'];
                    $mRating = $module['rating'];
                    $mPrice = $module['price'];

                    $modulesT .= '<tr>';
                    $modulesT .= '<td class="dark" style="width: 160px">' . $mName . '</td>';
                    $modulesT .= '<td class="dark" style="width: 30px; text-align: center">' . $mClass . $mRating . '</td>';
                    $modulesT .= '<td class="dark" style="width: 100px; text-align: right">' . number_format((float)$mPrice, 0) . ' cr</td>';
                    $modulesT .= '</tr>';
                }

                $modulesT .= '</table>';
                $modulesT .= '</td>';
            }
            $modulesT .= '</tr>';
            $modulesT .= '</table>';

            $sellingModules .= '<div class="heading" onclick="$(\'#modules_' . $stationId . '\').toggle();$(\'#outfitting_' . $stationId . '\').toggle()">';
            $sellingModules .= '<span style="color: #ccc; font-size: 10px">Modules</span>';
            $sellingModules .= '</div>';
            $sellingModules .= '<div id="modules_' . $stationId . '" style="display: none">' . $modulesT . '</div>';
        }

        $html .= $sellingModules;
        $html .= '</div>'; // holder
        $html .= '</div>'; // systeminfo_station
    }

    $stationResult->close();
    return $html;
}

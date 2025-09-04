<?php
/**
* Ajax backend file to fetch system data for System.php
*
* No description
*
* @package EDTB\Backend
* @author Mauri Kujala <contact@edtb.xyz>
* @copyright Copyright (C) 2016, Mauri Kujala
* @license http://www.gnu.org/licenses/old-licenses/gpl-2.0.html GNU Public License version 2
*/

/*
* ED ToolBox, a companion web app for the video game Elite Dangerous
* (C) 1984 - 2016 Frontier Developments Plc.
* ED ToolBox or its creator are not affiliated with Frontier Developments Plc.
*
* This program is free software; you can redistribute it and/or
* modify it under the terms of the GNU General Public License
* as published by the Free Software Foundation; either version 2
* of the License, or (at your option) any later version.
*
* This program is distributed in the hope that it will be useful,
* but WITHOUT ANY WARRANTY; without even the implied warranty of
* MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
* GNU General Public License for more details.
*
* You should have received a copy of the GNU General Public License
* along with this program; if not, write to the Free Software
* Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301, USA
*/

$ROOT = realpath(__DIR__ . '/..');
if (!isset($settings) || !is_array($settings)) {
    // Prefer server_config.inc.php / .redacted.php (Windows-parity)
    $cfg = null;
    if (is_file($ROOT . '/server_config.inc.php')) {
        $cfg = $ROOT . '/server_config.inc.php';
    } elseif (is_file($ROOT . '/server_config.inc.redacted.php')) {
        $cfg = $ROOT . '/server_config.inc.redacted.php';
    }

    if ($cfg) {
        $settings = require $cfg;
    } else {
        // Linux-parity: fall back to .env / .env.redacted in the repo root
        $envFile = null;
        if (is_file($ROOT . '/.env')) {
            $envFile = $ROOT . '/.env';
        } elseif (is_file($ROOT . '/.env.redacted')) {
            $envFile = $ROOT . '/.env.redacted';
        }

        if ($envFile) {
            $env = [];
            foreach (file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
                if ($line[0] === '#' || strpos($line, '=') === false) continue;
                [$k, $v] = explode('=', $line, 2);
                $v = trim($v, " \t\n\r\0\x0B\"'");
                $env[$k] = $v;
            }
            $settings = [
                'install_path' => $env['ROOT_DIR'] ?? $ROOT,
                'data_dir'     => $env['DATA_DIR'] ?? ($ROOT . '/data'),

                // DB params (names mapped to server_config keys)
                'db_host'      => $env['DB_HOST'] ?? '127.0.0.1',
                'db_name'      => $env['DB_NAME'] ?? 'edtb',
                'db_user'      => $env['DB_USER'] ?? 'edtb',
                'db_pass'      => $env['DB_PASS'] ?? '',
                'db_port'      => isset($env['DB_PORT']) ? (int)$env['DB_PORT'] : 3306,
            ];
        } else {
            die('Config not found: server_config.inc(.redacted).php or .env(.redacted) in ' . $ROOT);
        }
    }
}
require_once $ROOT . '/source/functions.php';
require_once $ROOT . '/source/MySQL.php';
require_once $ROOT . '/source/System.php';
require_once $ROOT . '/source/curSys.php';
require_once $ROOT . '/src/Domain/System/SystemRepository.php';
require_once $ROOT . '/src/Domain/Stations/StationsRepository.php';
require_once $ROOT . '/src/Domain/Rares/RaresRepository.php';
require_once $ROOT . '/System/Formatters/SystemInfoFormatters.php';




$data = ['si_name' => '', 'si_stations' => '', 'si_detailed' => ''];


use \EDTB\source\System;
$siDistAdd = '';
$curSys = is_array($curSys ?? null) ? $curSys : [];

/**
 * if system id or name is set, show info about that system
 */
$rawSystemName = $_GET['system_name'] ?? ($_GET['system'] ?? '');
$hasSystemId   = isset($_GET['system_id']) && $_GET['system_id'] !== 'undefined' && $_GET['system_id'] !== '';
$hasSystemName = $rawSystemName !== '' && $rawSystemName !== 'undefined';

// defaults to avoid "Undefined array key" + "Undefined variable" warnings
$siDistAdd = '';
$curSys = is_array($curSys ?? null) ? $curSys : [];
$curSys['x'] = $curSys['x'] ?? null;
$curSys['y'] = $curSys['y'] ?? null;
$curSys['z'] = $curSys['z'] ?? null;
$curSys['name'] = $curSys['name'] ?? '';

$siSystemName = '';
$siSystemDisplayName = '';
$siSystemState = 'None';
$siSystemSecurity = 'None';


if ($hasSystemId || $hasSystemName) {
    /** @var int $systemId */
    $systemId   = $hasSystemId ? (int)$_GET['system_id'] : -1;
    $escSysName = $hasSystemName ? $mysqli->real_escape_string(urldecode($rawSystemName)) : '';

    // If we only have a name, look up the id
    if ($systemId === -1 && $escSysName !== '') {
        $systemId = \EDTB\Domain\System\SystemRepository::findIdByName($mysqli, $escSysName) ?? -1;
    }
    $systemObj = \EDTB\Domain\System\SystemRepository::findById($mysqli, $systemId);


    $siSystemName         = !empty($systemObj->name) ? $systemObj->name : ($hasSystemName ? $escSysName : '');
    $siSystemDisplayName  = $siSystemName;

    // simbad ref on current system context
    $curSys['simbad_ref'] = isset($systemObj->simbad_ref) ? $systemObj->simbad_ref : '';

    $siSystemId           = $systemObj->id ?? null;
    $siSystemPopulation   = (isset($systemObj->population) && $systemObj->population !== '') ? $systemObj->population : 'None';
    $siSystemAllegiance   = (isset($systemObj->allegiance)   && $systemObj->allegiance   !== '') ? $systemObj->allegiance   : 'None';
    $siSystemEconomy      = (isset($systemObj->economy)      && $systemObj->economy      !== '') ? $systemObj->economy      : 'None';
    $siSystemGovernment   = (isset($systemObj->government)   && $systemObj->government   !== '') ? $systemObj->government   : 'None';
    $siSystemRulingFaction= (isset($systemObj->ruling_faction)&& $systemObj->ruling_faction!== '') ? $systemObj->ruling_faction: 'None';
    $siSystemState        = (isset($systemObj->state)        && $systemObj->state        !== '') ? $systemObj->state        : 'None';
    $siSystemSecurity     = (isset($systemObj->security)     && $systemObj->security     !== '') ? $systemObj->security     : 'None';
    $siSystemPower        = (isset($systemObj->power)        && $systemObj->power        !== '') ? $systemObj->power        : 'None';
    $siSystemPowerState   = (isset($systemObj->power_state)  && $systemObj->power_state  !== '') ? $systemObj->power_state  : 'None';

    // Distance from current or lastKnown system (null-safe)
    $curX = $curSys['x'] ?? null;
    $curY = $curSys['y'] ?? null;
    $curZ = $curSys['z'] ?? null;

    $sx = (float)$systemObj->si_system_coordx;
    $sy = (float)$systemObj->si_system_coordy;
    $sz = (float)$systemObj->si_system_coordz;

    if (validCoordinates($curX, $curY, $curZ)) {
        $adds  = '';
        $dist1 = sqrt((($curX - $sx) ** 2) + (($curY - $sy) ** 2) + (($curZ - $sz) ** 2));
    } else {
        // fallback to last known coordinates when curSys doesn't have coords yet
        $last = lastKnownSystem(); // expected: ['x'=>..., 'y'=>..., 'z'=>...]
        $lx = (float)($last['x'] ?? 0);
        $ly = (float)($last['y'] ?? 0);
        $lz = (float)($last['z'] ?? 0);
        $dist1 = sqrt((($lx - $sx) ** 2) + (($ly - $sy) ** 2) + (($lz - $sz) ** 2));
        $adds = ' *';
    }

    // Always set siDistAdd and update curSys coords for downstream use
    $siDistAdd = (($curSys['name'] ?? 'Current') . ': ' . number_format($dist1, 1) . ' ly' . $adds . ' - ');
    $curSys['x'] = $sx;
    $curSys['y'] = $sy;
    $curSys['z'] = $sz;


    $siDistAdd = $curSys['name'] . ': ' . number_format($dist1, 1) . ' ly' . $adds . ' - ';
    $curSys['x'] = $systemObj->si_system_coordx;
    $curSys['y'] = $systemObj->si_system_coordy;
    $curSys['z'] = $systemObj->si_system_coordz;

    if ($result) { $result->close(); }
}
/**
 * if system_id not set, show info about current system
 */
else {
    $siSystemName        = $curSys['name']        ?? '';
    $siSystemDisplayName = $siSystemName;

    $simbadRef = $curSys['simbad_ref'] ?? '';
    if ($simbadRef !== '') {
        // keep your existing display decoration here if desired
    }

    $siSystemId          = $curSys['id']          ?? null;
    $siSystemPopulation  = (isset($curSys['population'])  && $curSys['population']  !== '') ? $curSys['population']  : 'None';
    $siSystemAllegiance  = (isset($curSys['allegiance'])  && $curSys['allegiance']  !== '') ? $curSys['allegiance']  : 'None';
    $siSystemEconomy     = (isset($curSys['economy'])     && $curSys['economy']     !== '') ? $curSys['economy']     : 'None';
    $siSystemGovernment  = (isset($curSys['government'])  && $curSys['government']  !== '') ? $curSys['government']  : 'None';
    $siSystemRulingFaction = (isset($curSys['ruling_faction']) && $curSys['ruling_faction'] !== '') ? $curSys['ruling_faction'] : 'None';
    $siSystemState       = (isset($curSys['state'])       && $curSys['state']       !== '') ? $curSys['state']       : 'None';
    $siSystemPower       = (isset($curSys['power'])       && $curSys['power']       !== '') ? $curSys['power']       : 'None';
    $siSystemSecurity    = (isset($curSys['security'])    && $curSys['security']    !== '') ? $curSys['security']    : 'None';
    $siSystemPowerState  = (isset($curSys['power_state']) && $curSys['power_state'] !== '') ? $curSys['power_state'] : 'None';
}


$escSiSysName = $mysqli->real_escape_string($siSystemName);

/**
 * basic system info
 */

/**
 * get coordinates for distance calculations
 * and rares nearby
 */
if (validCoordinates($curSys['x'], $curSys['y'], $curSys['z'])) {
    $add3 = '';
    $udCoordx = $curSys['x'];
    $udCoordy = $curSys['y'];
    $udCoordz = $curSys['z'];

    /**
     * get rares closeby, if set to -1 = disabled
     */
    if (isset($settings['rare_range']) && $settings['rare_range'] == '-1') {
        $raresCloseby = 0;
    } else {
        // sanitize coords and search radius for the rares query
        $rx = (float)($curSys['x'] ?? 0);
        $ry = (float)($curSys['y'] ?? 0);
        $rz = (float)($curSys['z'] ?? 0);

        $range = (float)($settings['rare_range'] ?? 50.0);
        if ($range <= 0) { $range = 50.0; }

        $minX = $rx - $range; $maxX = $rx + $range;
        $minY = $ry - $range; $maxY = $ry + $range;
        $minZ = $rz - $range; $maxZ = $rz + $range;

        $rareResult   = \EDTB\Domain\Rares\RaresRepository::selectNearbyRaresResult($mysqli, $siSystemName, (float)($curSys['x'] ?? 0), (float)($curSys['y'] ?? 0), (float)($curSys['z'] ?? 0), (float)($settings['rare_range'] ?? 50.0));
        $raresCloseby = $rareResult ? $rareResult->num_rows : 0;



    }
} else {
    // get last known coordinates
    $lastCoords = lastKnownSystem();

    $lastCoordx = $lastCoords['x'] ?? null;
    $lastCoordy = $lastCoords['y'] ?? null;
    $lastCoordz = $lastCoords['z'] ?? null;

    $udCoordx = $lastCoordx;
    $udCoordy = $lastCoordy;
    $udCoordz = $lastCoordz;

    $add3 = ' *';


    $raresCloseby = 0;
}

/**
 * get distances to user defined systems
 */
$userDists = '<span class="right" style="font-size: 11px">' . $siDistAdd;

if (isset($settings['dist_systems'])) {
    $numDists = count($settings['dist_systems']);

    $i = 1;
    foreach ($settings['dist_systems'] as $distSys => $distSysDisplayName) {
        $escDistSys = $mysqli->real_escape_string($distSys);

        $userDistObj    = \EDTB\Domain\System\SystemRepository::findCoordsByNameOrUserOwn($mysqli, $escDistSys);
        $distSysId      = $userDistObj->id;
        $distSysCoordx  = $userDistObj->x;
        $distSysCoordy  = $userDistObj->y;
        $distSysCoordz  = $userDistObj->z;


        $userDist = sqrt((($udCoordx - $distSysCoordx) ** 2) + (($udCoordy - $distSysCoordy) ** 2) + (($udCoordz - $distSysCoordz) ** 2));
        $userDists .= '<a href="/System?system_id=' . $distSysId . '">' . $distSysDisplayName . '</a>: ' . number_format($userDist, 1) . ' ly' . $add3;

        if ($i != $numDists) {
            $userDists .= ' - ';
        }

        $i++;
    }
}
$userDists .= '</span>';

$cRaresData = '<div class="raresinfo" id="rares">';

/**
 * display rares nearby
 */
$actualNumRes = 0;

if ($raresCloseby > 0) {
    $actualNumRes = 0;

    while ($rareObj = $rareResult->fetch_object()) {
        if ($rareObj->distance <= $settings['rare_range']) {
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
            $cRaresData .= '</a>&nbsp;(';
            $cRaresData .= $rareObj->station;
            $cRaresData .= ')&nbsp;-&nbsp';
            $cRaresData .= number_format($rareObj->ls_to_star);
            $cRaresData .= '&nbsp;ls&nbsp';
            $cRaresData .= '(';
            $cRaresData .= $rareObj->sc_est_mins;
            $cRaresData .= '&nbsp;min)&nbsp';
            $cRaresData .= $rareObj->needs_permit = '1' ? '' : '&nbsp;-&nbsp;Permit needed';
            $cRaresData .= '-&nbsp';
            $cRaresData .= $rareObj->max_landing_pad_size;
            $cRaresData .= '</span><br><br>';
            $actualNumRes++;
        }
    }

    $rareResult->close();
} else {
    $cRaresData .= 'No rares nearby';
}

$cRaresData .= '</div>';

/**
 * provide crosslinks to screenshot gallery, log page, etc
 */
$siCrosslinks = buildSystemCrosslinks($siSystemName);

$numVisits = 0;
try {
    $ref = new \ReflectionMethod(System::class, 'numVisits');
    if ($ref->getNumberOfParameters() >= 2) {
        $numVisits = System::numVisits($mysqli, $siSystemName);
    } else {
        $numVisits = System::numVisits($siSystemName);
    }
} catch (\Throwable $e) {
    $numVisits = 0;
}

$rareText = buildRaresMiniLabel((int)$actualNumRes, $settings['rare_range'] ?? 50.0, $cRaresData, $curSys['x'] ?? null, $curSys['y'] ?? null, $curSys['z'] ?? null);

$data['si_name'] .= $siSystemDisplayName . $siCrosslinks;
$data['si_name'] .= '&nbsp;&nbsp;<span style="font-size: 11px;  text-transform: uppercase; vertical-align: middle">';

$data['si_name'] .= formatSiHeaderMeta($siSystemState, $siSystemSecurity, (int)$numVisits);
$data['si_name'] .= $rareText . $userDists . '</span>';



/* station info for System.php */
$__chk = $mysqli->query("SELECT 1 FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = 'edtb_stations'");
$hasStationsTable = ($__chk && $__chk->num_rows > 0); if ($__chk) { $__chk->close(); }
$stationExists = 0;

$stations = \EDTB\Domain\Stations\StationsRepository::findBySystemId($mysqli, (int)$systemId);

if ($hasStationsTable) {
    $stationResult = \EDTB\Domain\Stations\StationsRepository::selectResultBySystemId($mysqli, (int)$siSystemId);
    $stationExists = $stationResult->num_rows;
    

if ($stationExists == 0) {
    $data['si_stations'] = 'No station data available';
} else {
    while ($stationObj = $stationResult->fetch_object()) {
        $sName = $stationObj->name;
        $fullTitle = trim((string)$stationObj->name);
        $stationId = $stationObj->id;
        $sName = buildStationTitleWithWiki((int)$stationId, $fullTitle);

        $lsFromStar = $stationObj->ls_from_star;
        $maxLandingPadSize = $stationObj->max_landing_pad_size;

        $sFaction = empty($stationObj->faction) ? '' : '<strong>Faction:</strong> ' . $stationObj->faction;
        $sDistanceFromStar = $lsFromStar == 0 ? '' : '' . number_format($lsFromStar) . ' ls - ';
        $sInformation = '<span style="float: right;  margin-right: 8px">&boxur; &nbsp;' . $sDistanceFromStar . 'Landing pad: ' . $maxLandingPadSize . '</span><br>';
        $sGovernment = empty($stationObj->government) ? 'Government unknown' : $stationObj->government;
        $sAllegiance = empty($stationObj->allegiance) ? 'Allegiance unknown' : $stationObj->allegiance;

        $sState = empty($stationObj->state) ? '' : '<strong>State:</strong> ' . $stationObj->state . '<br>';
        $type = empty($stationObj->type) ? 'Type unknown' : $stationObj->type;
        $economies = empty($stationObj->economies) ? 'Economies unknown' : $stationObj->economies;
        $economies = empty($economies) ? 'Economies unknown' : $economies;

        $commodityLines = buildCommodityLines($stationObj);
        $sFaction = empty($stationObj->faction) ? '' : '<strong>Faction:</strong> ' . $stationObj->faction;
        $outfittingUpdatedAgo = !empty($stationObj->outfitting_updated_at) ? 'Outfitting last updated: ' . get_timeago($stationObj->outfitting_updated_at, true, true) : '';
        $shipyardUpdatedAgo = !empty($stationObj->shipyard_updated_at) ? ' (updated ' . get_timeago($stationObj->shipyard_updated_at, true, true) . ')' : '';

        $sellingShips = empty($stationObj->selling_ships) ? '' : str_replace("'", '', (string)$stationObj->selling_ships) . $shipyardUpdatedAgo;

        $sellingModules = '';

        /**
         * Information about the modules sold at the station
         */
        if (!empty($stationObj->selling_modules)) {
            $modules = $stationObj->selling_modules;

            $modulesS = explode('-', $modules);

            $modulesT = '';
            $lastClass = '';
            $lastModuleName = '';
            $lastCategoryName = '';

            
            $modCat = \EDTB\Domain\Stations\StationsRepository::modulesByIds($mysqli, $modulesS);


            arsort($modCat);

            $modulesT .= '<table style="margin-top: 10px">';
            $modulesT .= '<tr>';
            $modulesT .= '<td class="transparent" colspan="3" style="font-weight: 700">' . $outfittingUpdatedAgo . '</td>';
            $modulesT .= '</tr>';

            $modulesT .= '<tr style="vertical-align: top">';
            foreach ($modCat as $key => $value) {
                $mCategoryName = $key;
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

                    if ($mName !== $lastModuleName) {
                        $modulesT .= '<tr>';
                        $modulesT .= '<td class="dark" colspan="3">';
                        $modulesT .= '<strong>' . $mName . '</strong>';
                        $modulesT .= '</td>';
                        $modulesT .= '</tr>';
                        $lastClass = '';
                    }

                    $modulesT .= '<tr>';
                    if ($mClass !== $lastClass) {
                        $modulesT .= '<td class="light">Class ' . $mClass . '</td>';
                    } else {
                        $modulesT .= '<td class="transparent"></td>';
                    }

                    $modulesT .= '<td class="light">Rating ' . $mRating . '</td>';
                    $modulesT .= '<td class="light">Price ' . number_format($mPrice) . '</td>';

                    $lastModuleName = $mName;
                    $lastClass = $mClass;
                    $modulesT .= '</tr>';
                }
                $modulesT .= '</td></table>';
            }

            $modulesT .= '</tr></table>';

            $sellingModules = '<br><br><div onclick="$(\'#modules_' . $stationId . '\').fadeToggle(\'fast\')">';
            $sellingModules .= '<a href="javascript:void(0)"><img src="/style/img/plus.png" alt="plus" class="icon">Selling modules</a>';
            $sellingModules .= '</div>';
            $sellingModules .= '<div id="modules_' . $stationId . '" style="display: none">' . $modulesT . '</div>';
        }

        $shipyard = $stationObj->shipyard;
        $outfitting = $stationObj->outfitting;
        $commoditiesMarket = $stationObj->commodities_market;
        $blackMarket = $stationObj->black_market;
        $refuel = $stationObj->refuel;
        $repair = $stationObj->repair;
        $rearm = $stationObj->rearm;
        $isPlanetary = $stationObj->is_planetary;

        $icon = getStationIcon($type, $isPlanetary);

        $facilities = facilitiesFromStation($stationObj);

        $services = buildFacilitiesHtml($facilities, (int)$stationId);

        $info = $sFaction . $sInformation . $commodityLines;
        $info = str_replace("['", '', (string)$info);
        $info = str_replace(["']", "', '"], ['', ', '], (string)$info);

        $economies = str_replace("['", '', (string)$economies);
        $economies = str_replace(["']", "', '"], ['', ', '], (string)$economies);

        // get allegiance icon
        $allegianceIcon = getAllegianceIcon($sAllegiance);

        $data['si_stations'] .= '<div class="systeminfo_station" style="background-image: url(/style/img/' . $allegianceIcon . '); background-repeat: no-repeat; background-position: right 0 bottom -2px">';
        //$data["si_stations"] .= '<div class="heading" onclick="$(\'#info_'.$stationId.'\').toggle();$(\'#prices_'.$stationId.'\').toggle()">';
        $data['si_stations'] .= '<div class="heading">';
        $data['si_stations'] .= $icon . $sName;

        $data['si_stations'] .= '<span style="font-weight: 400; font-size: 10px">';
        $data['si_stations'] .= '&nbsp;[ ' . $type . ' - ' . $sAllegiance . ' - ' . $sGovernment . ' - ' . $economies . ' ]';
        $data['si_stations'] .= '</span>';

        $data['si_stations'] .= '<span class="right">';
        $data['si_stations'] .= '<a href="https://inara.cz/galaxy-station/?search=' . rawurlencode($stationObj->name . ' [' . $siSystemName . ']') . '" title="View station on INARA.cz" target="_blank">';
        $data['si_stations'] .= '<img src="/style/img/eddb.png" alt="EDDB" style="width: 10px; height: 12px">';
        $data['si_stations'] .= '</a>';
        $data['si_stations'] .= '</span>';

        $data['si_stations'] .= '</div>';

        $data['si_stations'] .= '<div class="wpsearch" id="wpsearch_' . $stationId . '" style="display: none"></div>';

        $data['si_stations'] .= '<div id="info_'. $stationId .'" class="systeminfo_station_info">';
        $data['si_stations'] .= $info;
        if ($info !== '') {
            $data['si_stations'] .= '<br>';
        }

        $data['si_stations'] .= $services;
        $data['si_stations'] .= $sellingShips;
        $data['si_stations'] .= $sellingModules;
        $data['si_stations'] .= '</div>';

        // prices information
        /**$query = "SELECT    listings.supply, listings.buy_price, listings.sell_price, listings.demand,
                                        commodities.name, commodities.average_price, commodities.category_id, commodities.category
                                        FROM listings
                                        LEFT JOIN commodities ON listings.commodity_id = commodities.id
                                        WHERE listings.station_id = '$stationEddbId'
                                        ORDER BY commodities.category_id");

        $data["si_stations"] .= '<div id="prices_'. $stationId .'" class="systeminfo_station_prices"><table width="100%">';

            $curCat = "";
            while ($arr3 = mysqli_fetch_assoc($pRes))
            {
                $categoryId = $arr3["category_id"];
                $category = $arr3["category"];
                $commodity = $arr3["name"];

                $supply = $arr3["supply"];
                $buy = $arr3["buy_price"];
                $sell = $arr3["sell_price"];
                $demand = $arr3["demand"];

                $maxProfit = $arr4["profit"];

                if ($curCat != $categoryId)
                {
                    $data["si_stations"] .= '<tr>';
                        $data["si_stations"] .= '<td class="light">' . $category . '</td>';
                        $data["si_stations"] .= '<td class="light">Supply</td>';
                        $data["si_stations"] .= '<td class="light">Buy price</td>';
                        $data["si_stations"] .= '<td class="light">Sell price</td>';
                        $data["si_stations"] .= '<td class="light">Demand</td>';
                    $data["si_stations"] .= '</tr>';
                }

                $data["si_stations"] .= '<tr>';
                    $data["si_stations"] .= '<td class="dark">' . $commodity . '</td>';
                    $data["si_stations"] .= '<td class="dark">' . number_format($supply) . '</td>';
                    $data["si_stations"] .= '<td class="dark">' . number_format($buy) . '</td>';
                    $data["si_stations"] .= '<td class="dark">' . number_format($sell) . '</td>';
                    $data["si_stations"] .= '<td class="dark">' . number_format($demand) . '</td>';
                $data["si_stations"] .= '</tr>';

                
            }
        $data["si_stations"] .= '</table></div>'; */

        $data['si_stations'] .= '</div>';
    }
}

$stationResult->close();
} else {
    $data['si_stations'] = 'No station data available';
}

/**
 * detailed system info
 */
$getSystemId   = $_GET['system_id']   ?? 'undefined';
$getSystemName = $_GET['system_name'] ?? 'undefined';

if ($stationExists == 0 && $getSystemId === 'undefined' && $getSystemName === 'undefined') {
    $data['si_detailed'] = 'No data available for this system';
} else {
    if ($siSystemPower !== 'None' && $siSystemPowerState !== 'None') {
        $escSystemPower = $mysqli->real_escape_string($siSystemPower);

        $hq = \EDTB\Domain\Powers\PowersRepository::getHQSystemName($mysqli, $siSystemPower);


        $siSystemData = '<a href="#" title="Headquarters: ' . $hq . '">' . $siSystemPower . '</a> [' . $siSystemPowerState . ']';
    } elseif (empty($siSystemPower) && empty($siSystemPowerState)) {
        $siSystemData = $siSystemPowerState;
    } else {
        $siSystemData = '';
    }

    $dispPopulation = is_numeric($siSystemPopulation) ? number_format($siSystemPopulation) : $siSystemPopulation;

    $data['si_detailed'] .= '<img src="/style/img/powers/' . str_replace(' ', '_', $siSystemPower) . '.jpg" class="powerpic" alt="' . $siSystemPower . '"><br>';
    $data['si_detailed'] .= '<span style="font-size: 13px; font-weight: 700">' . $siSystemData . '</span><br><br>';
    $__rows = [];
    if (!empty($siSystemAllegiance)    && $siSystemAllegiance    !== 'None') { $__rows[] = '<strong>Allegiance:</strong> ' . $siSystemAllegiance; }
    if (!empty($siSystemGovernment)    && $siSystemGovernment    !== 'None') { $__rows[] = '<strong>Government:</strong> ' . $siSystemGovernment; }
    if (is_numeric($siSystemPopulation) && (int)$siSystemPopulation > 0)      { $__rows[] = '<strong>Population:</strong> ' . number_format((int)$siSystemPopulation); }
    if (!empty($siSystemEconomy)       && $siSystemEconomy       !== 'None') { $__rows[] = '<strong>Economy:</strong> '    . $siSystemEconomy; }
    if (!empty($siSystemRulingFaction) && $siSystemRulingFaction !== 'None') { $__rows[] = '<strong>Faction:</strong> '    . $siSystemRulingFaction; }

    if (!empty($__rows)) {
        $data['si_detailed'] .= '<span>' . implode('<br>', $__rows) . '</span>';
    }


}
header('Content-Type: application/json; charset=UTF-8'); echo json_encode($data, JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE); exit;
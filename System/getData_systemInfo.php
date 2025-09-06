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
require_once $ROOT . '/System/Services/SystemInfoDetails.php';
require_once $ROOT . '/System/Services/SystemInfoStations.php';
require_once $ROOT . '/System/Services/SystemInfoRares.php';







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
    $siSystemPopulation   = (isset($systemObj->population)     && $systemObj->population     !== '') ? $systemObj->population     : 'None';
    $siSystemAllegiance   = (isset($systemObj->allegiance)     && $systemObj->allegiance     !== '') ? $systemObj->allegiance     : 'None';
    $siSystemEconomy      = (isset($systemObj->economy)        && $systemObj->economy        !== '') ? $systemObj->economy        : 'None';
    $siSystemGovernment   = (isset($systemObj->government)     && $systemObj->government     !== '') ? $systemObj->government     : 'None';
    $siSystemRulingFaction= (isset($systemObj->ruling_faction) && $systemObj->ruling_faction !== '') ? $systemObj->ruling_faction  : 'None';
    $siSystemState        = (isset($systemObj->state)          && $systemObj->state          !== '') ? $systemObj->state          : 'None';
    $siSystemSecurity     = (isset($systemObj->security)       && $systemObj->security       !== '') ? $systemObj->security       : 'None';
    $siSystemPower        = (isset($systemObj->power)          && $systemObj->power          !== '') ? $systemObj->power          : 'None';
    $siSystemPowerState   = (isset($systemObj->power_state)    && $systemObj->power_state    !== '') ? $systemObj->power_state    : 'None';


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
    $siSystemPopulation  = (isset($curSys['population'])   && $curSys['population']   !== '') ? $curSys['population']   : 'None';
    $siSystemAllegiance  = (isset($curSys['allegiance'])   && $curSys['allegiance']   !== '') ? $curSys['allegiance']   : 'None';
    $siSystemEconomy     = (isset($curSys['economy'])      && $curSys['economy']      !== '') ? $curSys['economy']      : 'None';
    $siSystemGovernment  = (isset($curSys['government'])   && $curSys['government']   !== '') ? $curSys['government']   : 'None';
    $siSystemRulingFaction = (isset($curSys['ruling_faction']) && $curSys['ruling_faction'] !== '') ? $curSys['ruling_faction'] : 'None';
    $siSystemState       = (isset($curSys['state'])        && $curSys['state']        !== '') ? $curSys['state']        : 'None';
    $siSystemPower       = (isset($curSys['power'])        && $curSys['power']        !== '') ? $curSys['power']        : 'None';
    $siSystemSecurity    = (isset($curSys['security'])     && $curSys['security']     !== '') ? $curSys['security']     : 'None';
    $siSystemPowerState  = (isset($curSys['power_state'])  && $curSys['power_state']  !== '') ? $curSys['power_state']  : 'None';

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
    list($rareResult, $raresCloseby) = fetchNearbyRares(
        $mysqli,
        (string)$siSystemName,
        (float)($curSys['x'] ?? 0),
        (float)($curSys['y'] ?? 0),
        (float)($curSys['z'] ?? 0),
        ($settings['rare_range'] ?? 50.0)
    );
    



    }
    else {
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
list($cRaresData, $actualNumRes, $rareText) = renderRaresBlock(
    $rareResult,
    (int)($raresCloseby ?? 0),
    $settings,
    $curSys
);


$rareText = buildRaresMiniLabel((int)$actualNumRes, $settings['rare_range'] ?? 50.0, $cRaresData, $curSys['x'] ?? null, $curSys['y'] ?? null, $curSys['z'] ?? null);

$data['si_name'] .= $siSystemDisplayName . $siCrosslinks;
$data['si_name'] .= '&nbsp;&nbsp;<span style="font-size: 11px;  text-transform: uppercase; vertical-align: middle">';
$data['si_name'] .= formatSiHeaderMeta($siSystemState, $siSystemSecurity, (int)$numVisits);
$data['si_name'] .= $rareText . $userDists . '</span>';
$data['si_stations'] = renderStationsHtml($mysqli, (int)$siSystemId);


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

        $data['si_detailed'] .= buildSystemDetailsHtml(
            $mysqli,
            $siSystemPower,
            $siSystemPowerState,
            $siSystemPopulation,
            $siSystemAllegiance,
            $siSystemGovernment,
            $siSystemEconomy,
            $siSystemRulingFaction
        );
    }


}
header('Content-Type: application/json; charset=UTF-8'); echo json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE); exit;
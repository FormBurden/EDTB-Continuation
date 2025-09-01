<?php
/**
 * Ajax backend file for the left column data
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

/** * System title for the left column */
$data['system_title'] = '';

$allegiance = $curSys['allegiance'] ?? '';
$pic = getAllegianceIcon($allegiance);

$data['system_title'] .= '<div class="titlewrap">';
$data['system_title'] .= '<div class="titleimg">';
$data['system_title'] .= $pic;
$data['system_title'] .= '</div>';

if (!isset($_COOKIE['style']) || $_COOKIE['style'] !== 'narrow') {
    $data['system_title'] .= '<div class="title">';

    // defaults
    $bookmarked = 0;
    $poid = 0;

    // guarded reads
    $curId   = $curSys['id']   ?? '';
    $curName = $curSys['name'] ?? '';
    $escName = $mysqli->real_escape_string($curName);

    // table existence checks (avoid fatals before migrations)
    $res = $mysqli->query("SHOW TABLES LIKE 'user_bookmarks'");
    $hasUserBookmarks = $res && $res->num_rows > 0;
    if ($res) { $res->close(); }

    $res = $mysqli->query("SHOW TABLES LIKE 'user_poi'");
    $hasUserPoi = $res && $res->num_rows > 0;
    if ($res) { $res->close(); }

    if ($hasUserBookmarks) {
        if ($curId !== '' && $curId !== '-1') {
            $bQuery = "SELECT id FROM user_bookmarks WHERE system_id = '" . $mysqli->real_escape_string($curId) . "' AND system_id != '' LIMIT 1";
        } else {
            $bQuery = "SELECT id FROM user_bookmarks WHERE system_name = '" . $escName . "' LIMIT 1";
        }
        if ($res = $mysqli->query($bQuery)) {
            $bookmarked = $res->num_rows;
            $res->close();
        }
    }

    if ($hasUserPoi) {
        $pQuery = "SELECT id FROM user_poi WHERE system_name = '" . $escName . "' AND system_name != '' LIMIT 1";
        if ($res = $mysqli->query($pQuery)) {
            $poid = $res->num_rows;
            $res->close();
        }
    }

    $class = $bookmarked > 0 ? 'bookmarked' : 'title';
    $class = $poid > 0 ? 'poid' : $class;

    $name = $curName;

    $data['system_title'] .= '<span class="' . $class . '">';

    if ($name !== '') {
        $data['system_title'] .= htmlspecialchars($name);
        $data['system_title'] .= '&nbsp;<img src="/style/img/copy.png" class="copy" title="Copy system name" />';
    } else {
        $data['system_title'] .= 'Location unavailable';
        $data['system_title'] .= '&nbsp;<img src="/style/img/help.png" class="help" title="Help" />';
        $data['system_title'] .= '<div class="helptext">';
        $data['system_title'] .= 'If you\'re having trouble getting ED ToolBox to show your current location, check the ';
        $data['system_title'] .= '<a href="https://edtb.xyz/common-issues" target="_blank">Common issues</a> page at EDTB.xyz for help.';
        $data['system_title'] .= '</div>';
    }

    $data['system_title'] .= '</span>';
    $data['system_title'] .= '</div>'; // .title
} else {
    $name = $curSys['name'] ?? '';
    $data['system_title'] .= '<div class="title">' . htmlspecialchars($name) . '</div>';
}

$data['system_title'] .= '</div>'; // .titlewrap

/**
 * User balance from FD API
 */
if (!isset($_COOKIE['style']) || $_COOKIE['style'] !== 'narrow') {
    $statusBalanceCache = '';
    if (isset($api['commander']) && $settings['show_cmdr_status'] === 'true' &&
        file_exists($_SERVER['DOCUMENT_ROOT'] . '/cache/cmdr_balance_status.html')) {
            $statusBalanceCache = file_get_contents($_SERVER['DOCUMENT_ROOT'] . '/cache/cmdr_balance_status.html');
        }
}

/**
 * System information for the left column
 */
$data['system_info'] = '';

if (!isset($_COOKIE['style']) || $_COOKIE['style'] !== 'narrow') {
    if (!empty($curSys['allegiance'])) {
        $populationS = $curSys['population'] == '0' ? '' : ' - Population: ' . number_format($curSys['population']);
        $governmentS = $curSys['government'] === '' ? '' : ' - ' . $curSys['government'];

        $data['system_info'] .= '<div class="subtitle" id="t2">' . $curSys['allegiance'] . $governmentS . $populationS . '</div>';

        $data['system_info'] .= '<div class="text" id="t3">';
        if (!empty($curSys['economy'])) {
            $data['system_info'] .= '&boxur; Economy: ' . $curSys['economy'] . '<span style="margin-left: 10px">';
        }
        $data['system_info'] .= '<span id="balance_st">' . $statusBalanceCache . '</span>';
        $data['system_info'] .= '</span></div>';
    } else {
        $data['system_info'] .= '<div class="subtitle" id="t2">Welcome</div>';
        $data['system_info'] .= '<div class="text" id="t3">';
        $data['system_info'] .= '&boxur; CMDR ' . $settings['cmdr_name'] . '<span style="margin-left: 10px">';
        $data['system_info'] .= '<span id="balance_st">' . $statusBalanceCache . '</span>';
        $data['system_info'] .= '</span></div>';
    }
}

/**
 * link to calculate coordinates
 */
if (empty($curSys['coordinates']) && !empty($curSys['name'])) {
    if (!isset($_COOKIE['style']) || $_COOKIE['style'] !== 'narrow') {
        $calcCoord .= '<span style="margin-bottom: 6px; height: 40px">';
        $calcCoord .= '<a href="javascript:void(0)" onclick="set_reference_systems(false);tofront(\'calculate\');get_cs(\'target_system\')" title="No coordinates found, click here to calculate">';
        $calcCoord .= '<img src="/style/img/calculator.png" class="icon24" alt="Calculate">';
        $calcCoord .= '&nbsp;*&nbsp;No coordinates, click to calculate them.</a></span><br><br>&nbsp';
    } else {
        $calcCoord .= '<span style="margin-bottom: 6px; text-align: center">';
        $calcCoord .= '<a href="javascript:void(0)" onclick="set_reference_systems(false);tofront(\'calculate\');get_cs(\'target_system\')" title="No coordinates found, click here to calculate">';
        $calcCoord .= '<img src="/style/img/calculator.png" class="icon24" alt="Calculate" style="margin-left: 11px; margin-top: 3px">';
        $calcCoord .= '</a></span>';
    }
}

/** 
 * Stations for the left column (null-safe)
 */
if (!isset($_COOKIE['style']) || $_COOKIE['style'] !== 'narrow') {
    // ensure these exist
    $stationData = $stationData ?? '';
    $calcCoord   = $calcCoord ?? '';

    // lock down the current system id
    $curSysId = (isset($curSys) && is_array($curSys) && isset($curSys['id']) && is_numeric($curSys['id']))
        ? (int)$curSys['id']
        : null;

    // check if stations table exists (avoid mysqli_sql_exception in strict mode)
    $hasStationsTable = false;
    if (isset($mysqli)) {
        $__chk = $mysqli->query("SHOW TABLES LIKE 'edtb_stations'");
        $hasStationsTable = ($__chk && $__chk->num_rows > 0);
        if ($__chk) { $__chk->close(); }
    }

    if ($curSysId !== null && $curSysId !== -1 && $hasStationsTable) {
        $query = "
            SELECT SQL_CACHE
                id, name, ls_from_star, max_landing_pad_size, faction, government, allegiance, state, type,
                import_commodities, export_commodities, prohibited_commodities, economies, selling_ships,
                shipyard, outfitting, commodities_market, black_market, refuel, repair, rearm, is_planetary
            FROM edtb_stations
            WHERE system_id = '{$curSysId}'
            ORDER BY -ls_from_star DESC, name
            LIMIT 5
        ";
        $result = $mysqli->query($query) or write_log($mysqli->error, __FILE__, __LINE__);

        if ($result && $result->num_rows > 0) {
            $c = 0;
            while ($stationObj = $result->fetch_object()) {
                if ($c === 0) {
                    $firstStationName       = $stationObj->name;
                    $firstStationLsFrom_star = $stationObj->ls_from_star;
                }

                $sType = $stationObj->type;
                $icon  = getStationIcon($sType, (int)$stationObj->is_planetary, 'margin:3px;margin-left:0px;margin-right:6px');

                // Build details lines
                $type    = $stationObj->type === ''                 ? '' : 'Type: ' . $stationObj->type . "\n";
                $pad     = $stationObj->max_landing_pad_size === '' ? '' : 'Landing pad: ' . $stationObj->max_landing_pad_size . "\n";
                $faction = $stationObj->faction === ''              ? '' : 'Faction: ' . $stationObj->faction . " \n";
                $gov     = $stationObj->government === ''           ? '' : 'Government: ' . $stationObj->government . " \n";
                $alleg   = $stationObj->allegiance === ''           ? '' : 'Allegiance: ' . $stationObj->allegiance . " \n";
                $state   = $stationObj->state === ''                ? '' : 'State: ' . $stationObj->state . " \n";
                $econ    = $stationObj->economies === ''            ? '' : 'Economies: ' . $stationObj->economies . " \n";

                $imports = $stationObj->import_commodities === ''   ? '' : " \nImport commodities: " . $stationObj->import_commodities . " \n";
                $exports = $stationObj->export_commodities === ''   ? '' : "Export commodities: " . $stationObj->export_commodities . "\n";
                $banned  = $stationObj->prohibited_commodities === '' ? '' : "Prohibited commodities: " . $stationObj->prohibited_commodities . " \n";

                // Strip quotes from selling_ships cleanly (no complicated escapes)
                $selling = $stationObj->selling_ships === '' ? '' : " \nSelling ships: " . str_replace("'", '', $stationObj->selling_ships) . "\n";

                // Facilities line
                $includes = [
                    'shipyard'            => $stationObj->shipyard,
                    'outfitting'          => $stationObj->outfitting,
                    'commodities market'  => $stationObj->commodities_market,
                    'black market'        => $stationObj->black_market,
                    'refuel'              => $stationObj->refuel,
                    'repair'              => $stationObj->repair,
                    'restock'             => $stationObj->rearm,
                ];
                $services = '';
                $i = 0;
                foreach ($includes as $nm => $included) {
                    if ((int)$included === 1) {
                        $services .= ($i === 0 ? 'Facilities: ' : ', ') . $nm;
                        $i++;
                    }
                }
                $services .= "\n";

                // Glue the info and normalize bracketed lists like ['X', 'Y']
                $info  = $type . $pad . $faction . $gov . $alleg . $state . $econ . $services . $imports . $exports . $banned . $selling;
                $info  = str_replace(['[', ']'], '', $info);
                $info  = str_replace("', '", ', ', $info);

                // One station entry
                $stationData .= "\n" . $icon . $stationObj->name;
                if ($stationObj->ls_from_star !== '' && $stationObj->ls_from_star !== null) {
                    $stationData .= ' (' . number_format((float)$stationObj->ls_from_star) . ' ls)';
                }
                $stationData .= ' ' . get_icon('log_add') . "\n";
                $stationData .= $info . "\n";

                $c++;
            }
            $result->close();
        } else {
            $stationData .= $calcCoord . 'No station data available';
            if ($result) { $result->close(); }
        }
    } else {
        // No ID yet, or table missing during Linux overhaul — just render calc link / placeholder
        $stationData .= $calcCoord;
    }
} else {
    $stationData .= $calcCoord;
}

$data['station_data'] = $stationData;


$data['station_data'] = $stationData;


/**
 * if system coords are user calculated, show calc button
 */
//$query = "  SELECT id, edsm_message
//            FROM user_systems_own
//            WHERE name = '$escCursysName'
//            LIMIT 1";
//
//$systemUserCalculated = $mysqli->query($query) or write_log($mysqli->error, __FILE__, __LINE__);
//
//$isUserCalculated = $systemUserCalculated->num_rows;
//
//if ($isUserCalculated > 0 && !empty($curSys["name"])) {
//    $cObj = $systemUserCalculated->fetch_object();
//    $edsmMs = $cObj->edsm_message;
//    $systemUserCalculated->close();
//
//    $parts = explode(":::", $edsmMs);
//
//    $msgNum = $parts[0];
//
//    /**
//     * ask for more distances
//     */
//    if ($msgNum != "102" && $msgNum != "104") {
//        if (!isset($_COOKIE["style"]) || $_COOKIE["style"] != "narrow") {
//            $stationData .= '<span style="float: right;  margin-right: 2px; margin-top: 6px">';
//        } else {
//            $stationData .= '<span style="float: right;  margin-top: 3px; text-align: center;white-space: nowrap">';
//        }
//        $stationData .= '<a href="javascript:void(0)" onclick="set_reference_systems(false, true);tofront(\'calculate\');get_cs(\'target_system\')" title="Supply more distances">';
//        $stationData .= '<img class="icon24" src="/style/img/calculator2.png" alt="Calculate">';
//        $stationData .= '</a><a href="javascript:void(0)" onclick="set_reference_systems(false);tofront(\'calculate\');get_cs(\'target_system\')" title="Review distances">';
//        $stationData .= '<img class="icon24" src="/style/img/calculator.png" alt="Calculate">';
//        $stationData .= '</a></span>';
//    } else {
//        /**
//         *  show review distances
//         */
//        if (!isset($_COOKIE["style"]) || $_COOKIE["style"] != "narrow") {
//            $stationData .= '<span style="float: right;  margin-right: 8px; margin-top: 6px">';
//        } else {
//            $stationData .= '<span style="float: right;  margin-top: 3px; margin-right: 13px;text-align: center">';
//        }
//        $stationData .= '<a href="javascript:void(0)" onclick="set_reference_systems(false);tofront(\'calculate\');get_cs(\'target_system\')" title="Review distances">';
//        $stationData .= '<img class="icon24" src="/style/img/calculator.png" alt="Calculate">';
//        $stationData .= '</a></span>';
//    }
//}

$data['station_data'] = $stationData;

<?php
/**
 * Main ajax backend file
 *
 * This file and required files are responsible for updating all of the on-the-fly stuff
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
/** JSON endpoints must never emit warnings/notices */
if (!headers_sent()) {
    header('Content-Type: application/json; charset=utf-8');
}
ini_set('display_errors', '0');
error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING);

/** @require config */
require_once __DIR__ . '/../source/config.inc.php';
/** @require functions */
require_once __DIR__ . '/../source/functions.php';
/** @require MySQL */
require_once __DIR__ . '/../source/MySQL.php';
/** @require curSys */
require_once __DIR__ . '/../source/curSys.php';
require_once __DIR__ . '/../src/Domain/Toolbox/ToolboxService.php';
/** safer array get */
function _g(array $a, string $k, $d=null) { return array_key_exists($k,$a) ? $a[$k] : $d; }



use \EDTB\Gallery\MakeGallery;

$action = $_GET['action'] ?? '';
// Normalize legacy/variant action names to canonical handlers
$__aliases = [
    'coords'        => 'onlycoordinates',
    'onlycoords'    => 'onlycoordinates',
    'coordinates'   => 'onlycoordinates',
    'system'        => 'onlysystem',
    'currentsystem' => 'onlysystem',
];
if (isset($__aliases[$action])) {
    $action = $__aliases[$action];
}

$request = $_GET['request'] ?? 0;
$newSystem = false; // default on cold start

if ($action === 'onlycoordinates') {
    $x = $curSys['x'] ?? 0;
    $y = $curSys['y'] ?? 0;
    $z = $curSys['z'] ?? 0;
    echo $x . ',' . $y . ',' . $z;
    exit;
}


if ($action === 'onlysystem') {
    echo $curSys['name'] ?? '';


    exit;
}

if ($action === 'onlyid') {
    echo $curSys['id'];

    exit;
}

if ($action === 'makegallery') {
    /**
     * make screenshot gallery
     */
    if (MakeGallery::go()) {
        $gallery = new MakeGallery();
        $gallery->makeGallery($curSys['name'] ?? '');
    }
    exit;
}

/** @var string $escCursysName */
$escCursysName = $mysqli->real_escape_string($curSys['name'] ?? '');

$data = [];



if ((isset($settings['nowplaying_file']) && !empty($settings['nowplaying_file'])) ||
    (isset($settings['nowplaying_vlc_password']) && !empty($settings['nowplaying_vlc_password']))
) {
    $data['now_playing'] = \EDTB\Domain\Toolbox\ToolboxService::nowPlaying($settings);
}



/**
 * If we've arrived in a new system or
 * are requesting page for the first time
 */
    if ($newSystem !== false || $request == 0) {

    $data['update_in_progress']    = 'false';
    $data['update_notification']   = '';
    $data['update_notification_data'] = 'false';

    $up = \EDTB\Domain\Toolbox\ToolboxService::updateStatusAndAutoUpdate($settings, $newSystem, (int)$request);
    $data['update_in_progress']      = $up['update_in_progress'];
    $data['update_notification']     = $up['update_notification'];
    $data['update_notification_data'] = $up['update_notification_data'];


        /**
     * update galmap json / new system tag / current system & coords
     * (extracted to service for ED Toolbox refactor)
     */
    $augment = \EDTB\Domain\Toolbox\ToolboxService::mapAndCurrent($curSys, $newSystem);
    $data['update_map']          = $augment['update_map'];
    $data['new_sys']             = $augment['new_sys'];
    $data['current_system_name'] = $augment['current_system_name'];
    $data['current_coordinates'] = $augment['current_coordinates'];   

    require __DIR__ . '/getData_leftColumn.php';


    require_once __DIR__ . '/../System/getData_systemInfo.php';

    /**
     * System and general logs
     */
    require_once __DIR__ . '/../Log/getData_logs.php';

    /**
     * Check for updates
     */
    require_once __DIR__ . '/../get/getData_checkForUpdates.php';

    /**
     * set data renew tag
     */
    $data['renew'] = 'true';
} else {
    $data['renew'] = 'false';
}
header('Content-Type: application/json; charset=UTF-8');
echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
exit;

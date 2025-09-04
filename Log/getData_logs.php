<?php
/**
 * ED ToolBox - Commander/System log feed (JSON payload builder)
 * Produces HTML (Markdown-ish) into $data['log_data'] for the main ED TOOLBOX tab.
 */

use EDTB\Log\MakeLog;

// Compose into $data['log_data']
$logdata = '';

// Current system name is provided by source/curSys.php
if (!empty($curSys['name'])) {
    // System-log sort (ascending / descending)
    $ssort = 'DESC';
    if (isset($_GET['slog_sort']) && $_GET['slog_sort'] !== 'undefined') {
        if ($_GET['slog_sort'] === 'asc')  $ssort = 'ASC';
        if ($_GET['slog_sort'] === 'desc') $ssort = 'DESC';
    }

    // Coordinates to measure distances from
    $coords = usableCoords(); // expects ['x','y','z','current']
    $usex   = $coords['x'];
    $usey   = $coords['y'];
    $usez   = $coords['z'];
    $escCursysName = $mysqli->real_escape_string($curSys['name']);

    // 1) System logs (range logic copied from the original design)
    if ((int)$settings['log_range'] === 0) {
        // Only logs for current system
        $query =
            "SELECT SQL_CACHE
                user_log.id,
                user_log.system_name AS log_system_name,
                user_log.station_id,
                user_log.log_entry,
                user_log.stardate,
                user_log.title,
                user_log.pinned,
                user_log.type,
                user_log.audio,
                edtb_systems.name  AS system_name,
                edtb_stations.name AS station_name
             FROM user_log
             LEFT JOIN edtb_systems  ON user_log.system_id   = edtb_systems.id
             LEFT JOIN edtb_stations ON user_log.station_id  = edtb_stations.id
             WHERE user_log.system_name = '$escCursysName'
             ORDER BY -user_log.pinned ASC, user_log.weight, user_log.stardate $ssort";
    } elseif ((int)$settings['log_range'] === -1) {
        // All logs (no range limit)
        $query =
            "SELECT SQL_CACHE
                user_log.id,
                user_log.system_name AS log_system_name,
                user_log.station_id,
                user_log.log_entry,
                user_log.stardate,
                user_log.title,
                user_log.pinned,
                user_log.type,
                user_log.audio,
                SQRT(
                    POW((IFNULL(edtb_systems.x, user_systems_own.x)-($usex)),2) +
                    POW((IFNULL(edtb_systems.y, user_systems_own.y)-($usey)),2) +
                    POW((IFNULL(edtb_systems.z, user_systems_own.z)-($usez)),2)
                ) AS distance,
                edtb_systems.name  AS system_name,
                edtb_stations.name AS station_name
             FROM user_log
             LEFT JOIN edtb_systems   ON user_log.system_name = edtb_systems.name
             LEFT JOIN edtb_stations  ON user_log.station_id  = edtb_stations.id
             LEFT JOIN user_systems_own ON user_log.system_name = user_systems_own.name
             WHERE user_log.system_name != ''
             ORDER BY -user_log.pinned ASC, user_log.weight, user_log.stardate $ssort";
    } else {
        // Logs within configured cube “radius” around last known (plus always current system)
        $R = (int)$settings['log_range'];
        $query =
            "SELECT SQL_CACHE
                user_log.id,
                user_log.system_id,
                user_log.system_name AS log_system_name,
                user_log.station_id,
                user_log.log_entry,
                user_log.stardate,
                user_log.title,
                user_log.pinned,
                user_log.type,
                user_log.audio,
                SQRT(
                    POW((IFNULL(edtb_systems.x, user_systems_own.x)-($usex)),2) +
                    POW((IFNULL(edtb_systems.y, user_systems_own.y)-($usey)),2) +
                    POW((IFNULL(edtb_systems.z, user_systems_own.z)-($usez)),2)
                ) AS distance,
                edtb_systems.name  AS system_name,
                edtb_stations.name AS station_name
             FROM user_log
             LEFT JOIN edtb_systems     ON user_log.system_name = edtb_systems.name
             LEFT JOIN edtb_stations    ON user_log.station_id  = edtb_stations.id
             LEFT JOIN user_systems_own ON user_log.system_name = user_systems_own.name
             WHERE IFNULL(edtb_systems.x, user_systems_own.x) BETWEEN $usex-$R AND $usex+$R
               AND IFNULL(edtb_systems.y, user_systems_own.y) BETWEEN $usey-$R AND $usey+$R
               AND IFNULL(edtb_systems.z, user_systems_own.z) BETWEEN $usez-$R AND $usez+$R
                OR user_log.system_name = '$escCursysName'
             ORDER BY -user_log.pinned ASC,
                      user_log.weight,
                      user_log.system_name = '$escCursysName' DESC,
                      distance ASC,
                      user_log.stardate $ssort
             LIMIT 10";
    }

    $sRes = $mysqli->query($query) or write_log($mysqli->error, __FILE__, __LINE__);
    if ($sRes->num_rows > 0) {
        $mk = new MakeLog();
        $mk->time_difference = $systemTime; // provided by environment
        $logdata .= $mk->makeLogEntries($sRes, 'system');
    }
    $sRes->close();
}

// 2) General logs (the “Commander’s Log” block)
$sort = 'DESC';
if (isset($_GET['glog_sort']) && $_GET['glog_sort'] !== 'undefined') {
    if ($_GET['glog_sort'] === 'asc')  $sort = 'ASC';
    if ($_GET['glog_sort'] === 'desc') $sort = 'DESC';
}

$qg =
    "SELECT SQL_CACHE id, log_entry, stardate, pinned, title, audio
     FROM user_log
     WHERE type = 'general'
     ORDER BY -pinned, weight, stardate $sort
     LIMIT 5";

$gRes = $mysqli->query($qg) or write_log($mysqli->error, __FILE__, __LINE__);
if ($gRes->num_rows > 0) {
    $mk = new MakeLog();
    $mk->time_difference = $systemTime;
    $logdata .= $mk->makeLogEntries($gRes, 'general');
}
$gRes->close();

// Empty state (“No log entries yet…”) — richer Toolbox panel
if ($logdata === '') {
    $logdata = <<<HTML
<header><h2>Commander's Log</h2></header>

<div class="toolbox-quicklinks" style="margin:8px 0 16px 0;font-size:12px;opacity:.9;">
  <a href="/#smuggling" title="Smuggling tips &amp; tricks">Smuggling tips &amp; tricks</a>
  &nbsp;&bull;&nbsp;
  <a href="/#mining" title="Mining tips &amp; tricks">Mining tips &amp; tricks</a>
  &nbsp;&bull;&nbsp;
  <a href="/#discounts" title="Ship Discounts">Ship Discounts</a>
  &nbsp;&bull;&nbsp;
  <a href="/#loadouts" title="Ship Loadouts">Ship Loadouts</a>
</div>

<div class="toolbox-section" style="margin:10px 0 20px 0;">
  <div class="toolbox-section-title" style="font-weight:600;letter-spacing:.5px;margin-bottom:6px;">Earth-like</div>
  <div class="toolbox-strip" style="height:140px;border-radius:8px;background:#0a0a0a url('/style/img/toolbox/earthlike_strip.jpg') center/cover no-repeat;"></div>
</div>

<div class="toolbox-section" style="margin:10px 0 20px 0;">
  <div class="toolbox-section-title" style="font-weight:600;letter-spacing:.5px;margin-bottom:6px;">Water</div>
  <div class="toolbox-strip" style="height:140px;border-radius:8px;background:#0a0a0a url('/style/img/toolbox/water_strip.jpg') center/cover no-repeat;"></div>
</div>

<div style="margin-top:10px;font-size:12px;">
  Nutter's explorers guide to the Galaxy <span style="opacity:.6">↗</span>
  <br>
  <div style="margin-top:6px;">Scoopable stars:<br> A, B, F, G, K, M, O</div>
</div>
HTML;
}


$data['log_data'] = $logdata;

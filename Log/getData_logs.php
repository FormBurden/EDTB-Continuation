<?php
/**
 * ED ToolBox - Commander/System log feed (JSON payload builder)
 * Produces HTML (Markdown-ish) into $data['log_data'] for the main ED TOOLBOX tab.
 */

use EDTB\Log\MakeLog;
/** Bootstrap config, helpers, DB, and current system */
require_once __DIR__ . '/../source/config.inc.php';
require_once __DIR__ . '/../source/functions.php';
require_once __DIR__ . '/../source/Helpers/Common.php';
require_once __DIR__ . '/../source/Helpers/Safe/Log.php';
require_once __DIR__ . '/../source/MySQL.php';
require_once __DIR__ . '/../source/curSys.php';

/** Class used below */
require_once __DIR__ . '/MakeLog.php';

/** Keep DB current with latest ED Journal entries (fast incremental) */
require_once __DIR__ . '/../source/Journal/Parser.php';
\EDTB\Journal\Parser::ingest();


$LOG_RANGE = isset($settings['log_range']) ? (int)$settings['log_range'] : 0;

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
    if ($LOG_RANGE === 0) {
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
    } elseif ($LOG_RANGE === -1) {
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
        $R = $LOG_RANGE;
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

// Empty state (“No log entries yet…”) — Journal-backed panel (Linux-native, richer)
if ($logdata === '') {

  /**
   * Parse the newest Journal forward so we can attach a System to Scan events.
   * Collect:
   *  - last 10 jumps (FSD/Carrier/Location)
   *  - last 10 ELW scans
   *  - last 10 Water world scans
   */
  $journalSummary = (function ($logDir) {
      $out = [
          'jumps' => [],   // each: ['time'=>ISO, 'system'=>string, 'dist'=>float|null]
          'elw'   => [],   // each: ['time'=>ISO, 'system'=>string, 'body'=>string]
          'ww'    => [],   // each: ['time'=>ISO, 'system'=>string, 'body'=>string]
      ];

      if (!$logDir || !is_dir($logDir)) {
          return $out;
      }

      $files = glob(rtrim($logDir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'Journal.*.log', GLOB_NOSORT);
      if (!$files) {
          return $out;
      }
      natsort($files);
      $path = end($files);

      $lines = @file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
      if (!$lines) {
          return $out;
      }

      $currentSystem = '';
      foreach ($lines as $row) {
          $j = json_decode($row, true);
          if (!is_array($j)) continue;

          $ev = $j['event'] ?? '';

          // Track current system from any event that carries StarSystem
          if (isset($j['StarSystem']) && $j['StarSystem'] !== '') {
              $currentSystem = $j['StarSystem'];
          }

          // Jumps / location updates
          if (($ev === 'FSDJump' || $ev === 'CarrierJump' || $ev === 'Location') && isset($j['StarSystem'])) {
              $out['jumps'][] = [
                  'time'   => $j['timestamp'] ?? '',
                  'system' => $j['StarSystem'],
                  'dist'   => isset($j['JumpDist']) ? (float)$j['JumpDist'] : null,
              ];
              if (count($out['jumps']) > 50) array_shift($out['jumps']); // cap memory
          }

          // Planet scans
          if ($ev === 'Scan' && isset($j['PlanetClass'])) {
              $pc   = strtolower($j['PlanetClass']);
              $body = $j['BodyName'] ?? '';
              $ts   = $j['timestamp'] ?? '';
              $sys  = $currentSystem;

              if (strpos($pc, 'earth-like') !== false) {
                  $out['elw'][] = ['time'=>$ts, 'system'=>$sys, 'body'=>$body];
                  if (count($out['elw']) > 50) array_shift($out['elw']);
              } elseif (strpos($pc, 'water world') !== false) {
                  $out['ww'][]  = ['time'=>$ts, 'system'=>$sys, 'body'=>$body];
                  if (count($out['ww'])  > 50) array_shift($out['ww']);
              }
          }
      }

      // Keep only the most recent N of each
      $out['jumps'] = array_slice(array_reverse($out['jumps']), 0, 10);
      $out['elw']   = array_slice(array_reverse($out['elw']),   0, 10);
      $out['ww']    = array_slice(array_reverse($out['ww']),    0, 10);

      return $out;
  })($settings['log_dir'] ?? null);

  // Counts for strip rows
  $elwCount = count($journalSummary['elw']);
  $wwCount  = count($journalSummary['ww']);
  $elwStrip = min(8, max(0, $elwCount));   // strip shows up to 8
  $wwStrip  = min(12, max(0, $wwCount));   // strip shows up to 12

  // Render helpers
  $renderPlanets = function (int $n, array $classes) {
      $html = '';
      for ($i = 0; $i < $n; $i++) {
          $cls = $classes[$i % count($classes)];
          $html .= '<div class="planet ' . $cls . '"></div>';
      }
      return $html;
  };
  $renderList = function (array $rows) {
      $h = '';
      foreach ($rows as $r) {
          $tsh = $r['time'] ? @date('d M Y, H:i', strtotime($r['time'])) : '';
          $h  .= '<div class="row"><span class="ts">'.htmlspecialchars($tsh ?: $r['time']).'</span> — ';
          if (!empty($r['body'])) {
              $h .= '<span class="body">'.htmlspecialchars($r['body']).'</span> ';
          }
          if (!empty($r['system'])) {
              $h .= '<span class="sys">@ '.htmlspecialchars($r['system']).'</span>';
          }
          $h .= '</div>';
      }
      return $h ?: '<div class="row" style="opacity:.7">No entries found in latest Journal.</div>';
  };

  // Inline CSS (scoped)
  $css = <<<CSS
<style>
.toolbox h2{margin:0 0 8px 0;font-weight:600}
.toolbox .toolbox-quicklinks a{color:#d0dbea;text-decoration:none}
.toolbox .toolbox-quicklinks a:hover{text-decoration:underline}
.toolbox .section{margin:12px 0 20px 0}
.toolbox .headline{font-weight:600;letter-spacing:.5px;margin-bottom:8px}
.toolbox .strip{border-radius:10px;padding:14px 16px;min-height:96px;background:#0b0b0b;box-shadow:inset 0 0 40px rgba(255,255,255,.04)}
.toolbox .planets{display:flex;gap:18px;align-items:center;flex-wrap:wrap}
.toolbox .planet{width:86px;height:86px;border-radius:50%;
box-shadow:0 0 0 2px rgba(255,255,255,.15), inset 0 10px 24px rgba(255,255,255,.18), inset 0 -8px 18px rgba(0,0,0,.55)}
/* Earth-like palette variants */
.toolbox .el1{background:
radial-gradient(120% 100% at 30% 30%, #e6f7ff55 0, #0000 40%),
radial-gradient(120% 120% at 70% 70%, #0a3a6055 0, #0000 55%),
linear-gradient(135deg,#7fb3d1,#8fc3df 40%,#b6d6ee 65%,#e8f1f7)}
.toolbox .el2{background:
radial-gradient(120% 100% at 32% 32%, #f6fff955 0, #0000 40%),
radial-gradient(120% 120% at 74% 70%, #0b3d4d55 0, #0000 55%),
linear-gradient(145deg,#9ecaa9,#a9d7b4 35%,#cfe8d5 70%,#eef7f0)}
.toolbox .el3{background:
radial-gradient(120% 100% at 28% 26%, #ffffff55 0, #0000 40%),
radial-gradient(120% 120% at 72% 74%, #1f4b7a55 0, #0000 55%),
linear-gradient(155deg,#6aa0c9,#7fb1d7 45%,#b8d3ec)}
.toolbox .el4{background:
radial-gradient(120% 100% at 30% 30%, #fff8 0, #0000 40%),
radial-gradient(120% 120% at 70% 70%, #204a3c55 0, #0000 55%),
linear-gradient(160deg,#79a78c,#89bb9b 45%,#c9e1d0)}
.toolbox .el5{background:
radial-gradient(120% 100% at 30% 30%, #fff8 0, #0000 40%),
radial-gradient(120% 120% at 70% 70%, #163a5455 0, #0000 55%),
linear-gradient(165deg,#78a9c4,#8fbad3 45%,#cfe2f0)}
.toolbox .el6{background:
radial-gradient(120% 100% at 30% 30%, #fff8 0, #0000 40%),
radial-gradient(120% 120% at 70% 70%, #214f7a55 0, #0000 55%),
linear-gradient(145deg,#86b2cf,#9cc2dc 45%,#d5e7f5)}
.toolbox .el7{background:
radial-gradient(120% 100% at 30% 30%, #fff6 0, #0000 40%),
radial-gradient(120% 120% at 70% 70%, #0e3b6a55 0, #0000 55%),
linear-gradient(150deg,#74a5cd,#8fb8da 45%,#d0e4f5)}
.toolbox .el8{background:
radial-gradient(120% 100% at 30% 30%, #fff6 0, #0000 40%),
radial-gradient(120% 120% at 70% 70%, #325d7f55 0, #0000 55%),
linear-gradient(170deg,#a4c3d8,#b6d1e3 45%,#e6f0f7)}
/* Water world palette variants */
.toolbox .ww1{background:
radial-gradient(120% 100% at 30% 30%, #fff8 0, #0000 40%),
radial-gradient(120% 120% at 70% 70%, #205a7f66 0, #0000 55%),
linear-gradient(155deg,#5c7fa3,#6f96b8 45%,#b7d0e6)}
.toolbox .ww2{background:
radial-gradient(120% 100% at 30% 30%, #fff8 0, #0000 40%),
radial-gradient(120% 120% at 70% 70%, #184f7366 0, #0000 55%),
linear-gradient(165deg,#6e9ec5,#81afd1 45%,#cfe3f2)}
.toolbox .ww3{background:
radial-gradient(120% 100% at 30% 30%, #fff8 0, #0000 40%),
radial-gradient(120% 120% at 70% 70%, #0e3d5f66 0, #0000 55%),
linear-gradient(175deg,#4f7ba6,#6794be 45%,#bfd7ee)}
.toolbox .ww4{background:
radial-gradient(120% 100% at 30% 30%, #fff8 0, #0000 40%),
radial-gradient(120% 120% at 70% 70%, #17415f66 0, #0000 55%),
linear-gradient(160deg,#5b86ad,#6e9ac0 45%,#bed4ea)}
.toolbox .ww5{background:
radial-gradient(120% 100% at 30% 30%, #fff8 0, #0000 40%),
radial-gradient(120% 120% at 70% 70%, #1e4d7366 0, #0000 55%),
linear-gradient(150deg,#6fa0c2,#80afcf 45%,#cfe4f4)}
.toolbox .ww6{background:
radial-gradient(120% 100% at 30% 30%, #fff8 0, #0000 40%),
radial-gradient(120% 120% at 70% 70%, #2a5a8266 0, #0000 55%),
linear-gradient(145deg,#769fbe,#89afcb 45%,#d2e4f5)}
.toolbox .recent .row{margin:4px 0}
.toolbox .recent .ts{opacity:.8;margin-right:6px}
.toolbox .recent .sys{font-weight:500}
.toolbox .recent .body{opacity:.95}
</style>
CSS;

  $elwHtml = $renderPlanets($elwStrip, ['el1','el2','el3','el4','el5','el6','el7','el8']);
  $wwHtml  = $renderPlanets($wwStrip,  ['ww1','ww2','ww3','ww4','ww5','ww6']);

  $recentJumpsHtml = '';
  if (!empty($journalSummary['jumps'])) {
      $recentJumpsHtml .= '<div class="section"><div class="headline">Recent activity</div>';
      $recentJumpsHtml .= '<div class="strip" style="padding:10px 14px;"><div class="recent">';
      foreach ($journalSummary['jumps'] as $jump) {
          $tsh = $jump['time'] ? @date('d M Y, H:i', strtotime($jump['time'])) : '';
          $recentJumpsHtml .= '<div class="row"><span class="ts">'.htmlspecialchars($tsh ?: $jump['time']).'</span> — ';
          $recentJumpsHtml .= '<span class="sys">'.htmlspecialchars($jump['system']).'</span>';
          if ($jump['dist'] !== null) {
              $recentJumpsHtml .= ' <span class="dist" style="opacity:.8">(' . number_format($jump['dist'], 1) . ' ly)</span>';
          }
          $recentJumpsHtml .= '</div>';
      }
      $recentJumpsHtml .= '</div></div></div>';
  }

  $recentElwHtml = '';
  if (!empty($journalSummary['elw'])) {
      $recentElwHtml .= '<div class="section"><div class="headline">Recent Earth-like scans</div>';
      $recentElwHtml .= '<div class="strip" style="padding:10px 14px;"><div class="recent">';
      $recentElwHtml .= $renderList($journalSummary['elw']);
      $recentElwHtml .= '</div></div></div>';
  }

  $recentWwHtml = '';
  if (!empty($journalSummary['ww'])) {
      $recentWwHtml .= '<div class="section"><div class="headline">Recent Water world scans</div>';
      $recentWwHtml .= '<div class="strip" style="padding:10px 14px;"><div class="recent">';
      $recentWwHtml .= $renderList($journalSummary['ww']);
      $recentWwHtml .= '</div></div></div>';
  }

  // Build the full HTML
  $logdata = <<<HTML
{$css}
<div class="toolbox">
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

{$recentJumpsHtml}

<div class="section">
  <div class="headline">Earth-like <span style="opacity:.7;font-weight:400">(last {$elwCount})</span></div>
  <div class="strip"><div class="planets">{$elwHtml}</div></div>
</div>

<div class="section">
  <div class="headline">Water <span style="opacity:.7;font-weight:400">(last {$wwCount})</span></div>
  <div class="strip"><div class="planets">{$wwHtml}</div></div>
</div>

{$recentElwHtml}
{$recentWwHtml}

<div style="margin-top:10px;font-size:12px;">
  Nutter's explorers guide to the Galaxy <span style="opacity:.6">↗</span><br>
  <div style="margin-top:6px;">Scoopable stars:<br> A, B, F, G, K, M, O</div>
</div>
</div>
HTML;
}





$data['log_data'] = $logdata;

<?php
/**
 * DataPoint Bootstrap — 1:1 server-side setup extracted from index.php
 * Notes:
 * - Expects session_start() already called by caller.
 * - Defines: $mysqli, $dataTable, $rows, $allFields, $fieldsInListView, $showText,
 *            $colIndex, $allowedTables, $linksMap, $config,
 *            $canDistance, $ad, $sort, $isDist, $nextAd, $distLabel,
 *            $quickFilters, $presets
 */

 /* ---- Core includes ---- */
require_once dirname(__DIR__) . '/source/config.inc.php';
require_once dirname(__DIR__) . '/source/config_ini.inc.php';
require_once dirname(__DIR__) . '/source/functions.php';

/* ---- Data Point helpers (from archive if present) ---- */
if (file_exists(__DIR__ . '/Formatter.php')) { require_once __DIR__ . '/Formatter.php'; }
if (file_exists(__DIR__ . '/functions.php')) { require_once __DIR__ . '/functions.php'; }

/* ---- Schema + per-table config ---- */
require_once __DIR__ . '/Schema.php';
require_once __DIR__ . '/Tables.php';

/* ---- DB handle + vendor globals ---- */
/** @var mysqli $mysqli */
global $mysqli, $server, $user, $pwd, $db, $settings;

/* Map config -> vendor globals */
$server = $server ?? ($settings['db_host'] ?? (defined('DB_HOST') ? DB_HOST : '127.0.0.1'));
if (!empty($settings['db_port'])) { $server .= ':' . (int)$settings['db_port']; }
$user   = $user ?? ($settings['db_user'] ?? (defined('DB_USER') ? DB_USER : 'root'));
$pwd    = $pwd  ?? ($settings['db_pass'] ?? (defined('DB_PASS') ? DB_PASS : ''));
$db     = $db   ?? ($settings['db_name'] ?? (defined('DB_NAME') ? DB_NAME : ''));

/* Ensure $mysqli exists and has a selected DB */
if (!($mysqli instanceof mysqli)) {
    $mysqli = @new mysqli($server, $user, $pwd, $db);
} else {
    $res = @$mysqli->query('SELECT DATABASE()');
    $row = $res ? $res->fetch_row() : null;
    if ($res) { $res->close(); }
    if (!$row || !$row[0]) { if ($db !== '') { @$mysqli->select_db($db); } }
}

/* ---- Allowed tables & default selection ---- */
$allowedTables = datapoint_table_whitelist($mysqli);

/* Prefer edtb_systems first in list */
usort($allowedTables, function($a, $b) {
    if ($a === 'edtb_systems') return -1;
    if ($b === 'edtb_systems') return 1;
    return strcmp($a, $b);
});

/* Remember last table */
if (isset($_GET['table'])) {
    $_SESSION['dp_last_table'] = (string)$_GET['table'];
}
$last = isset($_SESSION['dp_last_table']) ? (string)$_SESSION['dp_last_table'] : '';

$requested        = isset($_GET['table']) ? (string)$_GET['table'] : ($last ?: '');
$preferredDefault = in_array('edtb_systems', $allowedTables, true) ? 'edtb_systems' : ($allowedTables[0] ?? '');
$dataTable        = in_array($requested, $allowedTables, true) ? $requested : $preferredDefault;

/* ---- Rows-per-page (persist) ---- */
$rows = isset($_GET['rows']) ? (int)$_GET['rows'] : (isset($_SESSION['dp_rows']) ? (int)$_SESSION['dp_rows'] : 25);
if (!in_array($rows, [10,25,50,100], true)) { $rows = 25; }
$_SESSION['dp_rows'] = $rows;

/* ---- Columns + labels (auto) ---- */
list($allFields, $autoLabels) = datapoint_get_column_labels($mysqli, $dataTable);

/* ---- Per-table overrides (curated list + label overrides + filters + presets) ---- */
$config = function_exists('datapoint_config_for_table') ? datapoint_config_for_table($dataTable) : [];

if (!empty($config['list'])) {
    $fieldsInListView = [];
    foreach ($config['list'] as $f) {
        if (in_array($f, $allFields, true)) { $fieldsInListView[] = $f; }
    }
    if (!$fieldsInListView) { $fieldsInListView = $allFields; }
} else {
    $fieldsInListView = array_slice($allFields, 0, 7);
}

$showText = $autoLabels;
if (!empty($config['labels'])) {
    foreach ($config['labels'] as $k => $v) { $showText[$k] = $v; }
}

/* Precompute column index map for client-side formatters */
$colIndex = [];
foreach ($fieldsInListView as $i => $f) { $colIndex[$f] = $i; }

/* ---- Build full tab list (linksToDb) ---- */
$linksMap = [];
foreach ($allowedTables as $t) {
    if (function_exists('datapoint_table_title')) {
        $linksMap[$t] = datapoint_table_title($t);
    } else {
        $linksMap[$t] = strtoupper($t);
    }
}

/* ---- Distance sort availability ---- */
$canDistance = false;
if ($dataTable === 'edtb_systems' || $dataTable === 'edtb_stations') {
    $canDistance = true;
} else {
    $hasXYZ = in_array('x', $allFields, true) && in_array('y', $allFields, true) && in_array('z', $allFields, true);
    $hasSys = in_array('system_name', $allFields, true) || in_array('system_id', $allFields, true);
    $canDistance = $hasXYZ || $hasSys;
}

$ad       = isset($_GET['ad']) ? (string)$_GET['ad'] : 'a';
$sort     = isset($_GET['sort']) ? (string)$_GET['sort'] : '';
$isDist   = ($sort === 'distance');
$nextAd   = ($ad === 'a') ? 'd' : 'a';
$distLabel = 'Distance ' . ($ad === 'a' ? '▼' : '▲');

/* Derived convenience for client */
$quickFilters = isset($config['quick_filters']) ? $config['quick_filters'] : [];
$presets      = isset($config['presets']) ? $config['presets'] : [];

/* ---- Client state for datapoint.js ---- */
$DP_STATE = [
    'table'        => $dataTable,
    'fields'       => array_values($fieldsInListView),
    'labels'       => $showText,
    'colIndex'     => $colIndex,
    'format'       => isset($config['format']) ? $config['format'] : [],
    'quickFilters' => $quickFilters,
    'presets'      => $presets,
    'rows'         => (int)$rows,
];

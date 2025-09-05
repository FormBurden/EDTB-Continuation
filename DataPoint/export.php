<?php
/**
 * Data Point CSV export
 * - Whitelists table via datapoint_table_whitelist()
 * - Uses per-table 'list' if present, else all columns from Schema.php
 */

require_once dirname(__DIR__) . '/source/config.inc.php';
require_once dirname(__DIR__) . '/source/config_ini.inc.php';
require_once dirname(__DIR__) . '/source/functions.php';
require_once __DIR__ . '/Schema.php';
require_once __DIR__ . '/Tables.php';

global $mysqli, $settings;

/* Build mysqli if not present */
if (!($mysqli instanceof mysqli)) {
    $host = $settings['db_host'] ?? '127.0.0.1';
    if (!empty($settings['db_port'])) { $host .= ':' . (int)$settings['db_port']; }
    $user = $settings['db_user'] ?? 'root';
    $pass = $settings['db_pass'] ?? '';
    $db   = $settings['db_name'] ?? '';
    $mysqli = @new mysqli($host, $user, $pass, $db);
}

/* Validate table */
$table   = isset($_GET['table']) ? (string)$_GET['table'] : '';
$allowed = datapoint_table_whitelist($mysqli);
if (!$table || !in_array($table, $allowed, true)) {
    http_response_code(400);
    header('Content-Type: text/plain; charset=utf-8');
    echo "Invalid table.";
    exit;
}

/* Resolve columns */
list($allFields, $labels) = datapoint_get_column_labels($mysqli, $table);
$config = function_exists('datapoint_config_for_table') ? datapoint_config_for_table($table) : [];

$cols = [];
if (!empty($config['list'])) {
    foreach ($config['list'] as $f) { if (in_array($f, $allFields, true)) { $cols[] = $f; } }
}
if (!$cols) { $cols = $allFields; }

/* Build and run query */
$colList = '`' . implode('`,`', array_map([$mysqli, 'real_escape_string'], $cols)) . '`';
$sql = "SELECT {$colList} FROM `".$mysqli->real_escape_string($table)."`";
$res = $mysqli->query($sql);
if (!$res) {
    http_response_code(500);
    header('Content-Type: text/plain; charset=utf-8');
    echo "Query failed.";
    exit;
}

/* Stream CSV */
$filename = $table . '_' . date('Ymd_His') . '.csv';
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="'.$filename.'"');

$out = fopen('php://output', 'w');

/* Header row (friendly labels where available) */
$headerRow = [];
foreach ($cols as $c) { $headerRow[] = $labels[$c] ?? $c; }
fputcsv($out, $headerRow);

/* Data rows */
while ($row = $res->fetch_assoc()) {
    $line = [];
    foreach ($cols as $c) {
        $val = $row[$c];
        if (is_null($val)) $val = '';
        $line[] = $val;
    }
    fputcsv($out, $line);
}
fclose($out);
$res->free();

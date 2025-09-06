<?php
/**
 * Data Point — original-repo features v2
 * - Tabs across tables (vendor linksToDb)
 * - Remembers last table
 * - Rows-per-page persisted (10/25/50/100)
 * - CSV export endpoint (export.php)
 * - Distance sort toggle (when applicable)
 * - NEW: Per-table Quick Filters (chips that auto-fill vendor search and submit)
 * - NEW: Column Visibility Presets (per-table, hides/show columns client-side)
 * - NEW: Boolean inputs in edit forms (checkboxes injected for bool fields)
 */

session_start();

/* ---- Core includes ---- */
require_once dirname(__DIR__) . '/source/config.inc.php';
require_once dirname(__DIR__) . '/source/config_ini.inc.php';
require_once dirname(__DIR__) . '/source/functions.php';

/* ---- UI header (no Theme dependency) ---- */
require_once dirname(__DIR__) . '/style/Header.php';

/* ---- Data Point helpers (from archive if present) ---- */
if (file_exists(__DIR__ . '/Formatter.php')) { require_once __DIR__ . '/Formatter.php'; }
if (file_exists(__DIR__ . '/functions.php')) { require_once __DIR__ . '/functions.php'; }

/* ---- Schema + per-table config ---- */
require_once __DIR__ . '/Schema.php';
require_once __DIR__ . '/Tables.php';

/* ---- Safety shim ONLY IF setData is missing (keeps vendor happy) ---- */
if (!function_exists('setData')) {
    function setData($key, $value, $dX = null, $dY = null, $dZ = null, &$dist = null, $table = null, $enum = null) {
        $label = htmlspecialchars((string)$key);
        $val   = htmlspecialchars(is_scalar($value) ? (string)$value : json_encode($value));
        return "<td class=\"datapoint_td\" title=\"{$label}\">{$val}</td>";
    }
}

/* ---- Vendor table editor (unchanged) ---- */
require_once __DIR__ . '/Vendor/MySQL_table_edit/mte.php';

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

/* ---- Configure vendor instance ---- */
$tabledit = new MySQLtabledit();

$tabledit->table               = $dataTable;
$tabledit->links_to_db         = $linksMap;
$tabledit->linksToDb           = $linksMap;
$tabledit->primary_key         = 'id';
$tabledit->primaryKey          = 'id';
$tabledit->fields_in_list_view = $fieldsInListView;
$tabledit->fieldsInListView    = $fieldsInListView;
$tabledit->show_text           = $showText;
$tabledit->showText            = $showText;
$tabledit->num_rows_list_view  = $rows;
$tabledit->numRowsListView     = $rows;
$tabledit->url_base            = 'Vendor/MySQL_table_edit/';
$tabledit->urlBase             = 'Vendor/MySQL_table_edit/';
$tabledit->url_script          = '/DataPoint';
$tabledit->urlScript           = '/DataPoint';

if (!empty($config['order_by'])) { $tabledit->order_by = $config['order_by']; }

/* ---- Distance sort availability ---- */
$canDistance = false;
if ($dataTable === 'edtb_systems' || $dataTable === 'edtb_stations') {
    $canDistance = true;
} else {
    $hasXYZ = in_array('x', $allFields, true) && in_array('y', $allFields, true) && in_array('z', $allFields, true);
    $hasSys = in_array('system_name', $allFields, true) || in_array('system_id', $allFields, true);
    $canDistance = $hasXYZ || $hasSys;
}

$ad  = isset($_GET['ad']) ? (string)$_GET['ad'] : 'a';
$sort = isset($_GET['sort']) ? (string)$_GET['sort'] : '';
$isDist = ($sort === 'distance');
$nextAd = ($ad === 'a') ? 'd' : 'a';
$distLabel = 'Distance ' . ($ad === 'a' ? '▼' : '▲');

/* Derived convenience for client */
$quickFilters = isset($config['quick_filters']) ? $config['quick_filters'] : [];
$presets      = isset($config['presets']) ? $config['presets'] : [];

/* ---- Render ---- */
$header = new Header();
$UPPER  = strtoupper($dataTable);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Data Point — <?php echo htmlspecialchars($UPPER); ?></title>
    <?php
    if (method_exists($header, 'displayCss')) { $header->displayCss(); }
    else { echo '<link rel="stylesheet" href="/style/style.css">'; }
    ?>
    <link rel="stylesheet" href="/DataPoint/assets/datapoint.css">
    <script>
    window.DP_STATE = {
      table: <?php echo json_encode($dataTable); ?>,
      fields: <?php echo json_encode(array_values($fieldsInListView)); ?>,
      labels: <?php echo json_encode($showText); ?>,
      colIndex: <?php echo json_encode($colIndex); ?>,
      format: <?php echo json_encode(isset($config['format']) ? $config['format'] : []); ?>,
      quickFilters: <?php echo json_encode($quickFilters); ?>,
      presets: <?php echo json_encode($presets); ?>,
      rows: <?php echo (int)$rows; ?>
   };
</script>
<script src="/DataPoint/assets/datapoint.js"></script>
</head>
<body>
<?php if (method_exists($header, 'displayHeader')) { $header->displayHeader('Data Point'); } ?>

<div class="container" style="padding: 10px 15px;">
    <!-- Classic title pill -->
    <?php include __DIR__ . '/partials/topbar.php'; ?>


    <!-- Optional table switcher (hidden by default—click gear) -->
    <?php include __DIR__ . '/partials/switcher.php'; ?>


    <!-- Top controls: Rows-per-page + Export + Distance sort + Preset -->
    <?php include __DIR__ . '/partials/controls.php'; ?>

        <div class="right">
            <a class="dp-button" href="/DataPoint/export.php?table=<?php echo urlencode($dataTable); ?>">Export CSV</a>
        </div>
    </div>

    <!-- Quick Filters -->
    <div id="dp-quickfilters"></div>

    <div id="data-view" class="dp-mte">
        <?php $tabledit->do_it(); ?>
    </div>
</div>

</body>
</html>

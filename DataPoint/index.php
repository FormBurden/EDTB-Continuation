<?php
/**
 * Data Point — closer to original repo (tabs, distance sort, memory)
 * - Ensures DB globals for vendor
 * - Uses original helpers when present (Formatter.php, functions.php)
 * - Default table edtb_systems; per-table curation via Tables.php
 * - Rows-per-page persisted (10/25/50/100)
 * - One-click CSV export (export.php)
 * - Tabs across ALL allowed tables (vendor renders them via linksToDb)
 * - Distance sort toggle (adds ?sort=distance&ad=...)
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

/* ---- Per-table overrides (curated list + label overrides) ---- */
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
    <style>
    :root{
      --dp-accent:#2ca0c9;
      --dp-accent-press:#1f87a9;
      --dp-ink:#cfe8f1;
      --dp-ink-dim:#9fb8c3;
      --dp-border:#2a3742;
    }

    /* Title pill bar (classic) */
    .dp-title-pill{
      display:inline-block; padding:8px 14px; border-radius:6px;
      background:var(--dp-accent); color:#fff; font-weight:600; letter-spacing:.5px;
      text-transform:uppercase; box-shadow:0 1px 0 rgba(0,0,0,.4);
    }
    .dp-bar{ display:flex; align-items:center; justify-content:center; gap:8px; margin:10px 0 6px; }
    .dp-gear{ cursor:pointer; opacity:.8; user-select:none; }
    .dp-gear:hover{ opacity:1; }
    .dp-switch{ display:none; margin:0 8px 8px; text-align:center; }

    .dp-controls{
      display:flex; justify-content:space-between; align-items:center;
      margin:8px 0 10px; gap:8px; flex-wrap:wrap;
    }
    .dp-controls .left, .dp-controls .right{ display:flex; align-items:center; gap:8px; }
    .dp-button{
      appearance:none; background:var(--dp-accent); color:#fff; border:0;
      border-radius:6px; padding:8px 14px; font-weight:600; letter-spacing:.25px;
      box-shadow:0 1px 0 rgba(0,0,0,.4); cursor:pointer; text-transform:uppercase; font-size:12.5px;
    }
    .dp-button:hover{ filter:brightness(1.08); }
    .dp-button:active{ transform:translateY(1px); background:#1f87a9; }

    /* ---------- DataPoint scoped styles ---------- */
    .dp-mte{
      --padY:8px; --padX:14px; --radius:6px;
      color:var(--dp-ink);
    }
    .dp-mte select,
    .dp-mte input[type="text"],
    .dp-mte input[type="search"]{
      background:#0f1419; color:var(--dp-ink);
      border:1px solid var(--dp-border); border-radius:6px; padding:7px 10px; outline:none;
    }
    .dp-mte select:focus,
    .dp-mte input[type="text"]:focus,
    .dp-mte input[type="search"]:focus{
      border-color:var(--dp-accent); box-shadow:0 0 0 2px rgba(44,160,201,.2);
    }
    .dp-mte button,
    .dp-mte input[type="submit"],
    .dp-mte input[type="button"]{
      appearance:none; background:var(--dp-accent);
      color:#fff; border:0; border-radius:6px; padding:8px 14px;
      font-weight:600; letter-spacing:.25px; box-shadow:0 1px 0 rgba(0,0,0,.4);
      cursor:pointer; text-transform:uppercase; font-size:12.5px;
    }
    .dp-mte [disabled]{ opacity:.55; cursor:not-allowed; }

    /* Ghost / secondary buttons */
    .dp-mte input[type="submit"][value*="Reset" i],
    .dp-mte input[type="submit"][value*="Cancel" i]{
      background:transparent; color:var(--dp-ink);
      border:1px solid var(--dp-border); text-transform:uppercase;
    }

    /* Specific labels */
    .dp-mte input[type="submit"][value="SEARCH"],
    .dp-mte input[type="submit"][value="Search"],
    .dp-mte input[type="submit"][value*="Search" i]{ padding:7px 12px; font-size:12px; }

    /* Tables */
    .dp-mte table{ width:100%; border-collapse:collapse; }
    .dp-mte thead th{
      background:#0b1015; color:#fff; font-weight:700; text-transform:capitalize;
      border-bottom:2px solid var(--dp-border); padding:9px 8px;
    }
    .dp-mte tbody td{ border-top:1px solid var(--dp-border); padding:9px 8px; }
    .dp-mte tbody tr:hover{ background:#111820; }

    /* Pagination variants */
    .dp-mte .mte_navigation, .dp-mte .mte_pages, .dp-mte .pages{
      display:flex; align-items:center; gap:6px; margin:8px 0 12px;
    }
    .dp-mte .mte_navigation a, .dp-mte .mte_pages a, .dp-mte .pages a{
      display:inline-block; padding:4px 9px; border-radius:5px;
      background:#0f1419; color:#9fb8c3; text-decoration:none;
      border:1px solid var(--dp-border);
    }
    .dp-mte .mte_navigation a:hover, .dp-mte .mte_pages a:hover, .dp-mte .pages a:hover{
      color:#fff; border-color:var(--dp-accent);
    }
    .dp-mte .mte_navigation strong, .dp-mte .mte_pages strong, .dp-mte .pages strong{
      display:inline-block; padding:4px 9px; border-radius:5px;
      background:#2ca0c9; color:#fff; border:1px solid transparent;
    }

    /* ul.pagination + .mte_nav/mtelink + prev/next */
    .dp-mte ul.pagination{
      display:flex; align-items:center; justify-content:center;
      gap:6px; margin:8px 0 12px; padding:0; list-style:none;
    }
    .dp-mte ul.pagination:empty{ display:none; }
    .dp-mte .mte_nav a.mtelink,
    .dp-mte .mte_nav_prev_next{
      display:inline-block; padding:4px 9px; border-radius:5px;
      background:#0f1419; color:#9fb8c3; text-decoration:none;
      border:1px solid var(--dp-border);
    }

    /* Boolean badges */
    .dp-bool { display:inline-block; padding:2px 7px; border-radius:10px; font-size:12px; font-weight:700; }
    .dp-bool.yes { background:#1b6f1b; color:#fff; }
    .dp-bool.no  { background:#6f1b1b; color:#fff; }
    </style>
    <script>
    // Client-side state for boolean formatting
    window.DP_STATE = {
      table: <?php echo json_encode($dataTable); ?>,
      fields: <?php echo json_encode(array_values($fieldsInListView)); ?>,
      colIndex: <?php echo json_encode($colIndex); ?>,
      format: <?php echo json_encode(isset($config['format']) ? $config['format'] : []); ?>,
      rows: <?php echo (int)$rows; ?>
    };
    document.addEventListener('DOMContentLoaded', function(){
      var st = window.DP_STATE;
      if (!st || !st.fields || !st.fields.length) return;

      // Find the first data table whose header matches the fields count
      var tables = document.querySelectorAll('.dp-mte table');
      var listTable = null;
      tables.forEach(function(t){
        var ths = t.querySelectorAll('thead th');
        if (!listTable && ths && ths.length === st.fields.length) { listTable = t; }
      });
      if (!listTable) return;

      // Boolean formatting
      var fmt = st.format || {};
      var boolCols = [];
      for (var key in fmt) {
        if (fmt[key] === 'bool' && st.colIndex.hasOwnProperty(key)) {
          boolCols.push(st.colIndex[key]);
        }
      }
      if (boolCols.length) {
        var rows = listTable.querySelectorAll('tbody tr');
        rows.forEach(function(r){
          var cells = r.children;
          boolCols.forEach(function(idx){
            var c = cells[idx];
            if (!c) return;
            var raw = (c.textContent || '').trim();
            if (raw === '') return;
            var yes = /^(1|true|yes)$/i.test(raw);
            var no  = /^(0|false|no)$/i.test(raw);
            if (yes || no) {
              c.innerHTML = '<span class="dp-bool '+(yes?'yes':'no')+'">'+(yes?'Yes':'No')+'</span>';
            }
          });
        });
      }
    });
    </script>
</head>
<body>
<?php if (method_exists($header, 'displayHeader')) { $header->displayHeader('Data Point'); } ?>

<div class="container" style="padding: 10px 15px;">
    <!-- Classic title pill -->
    <div class="dp-bar">
        <span class="dp-title-pill"><?php echo htmlspecialchars($UPPER); ?></span>
        <span class="dp-gear" onclick="(function(){var s=document.getElementById('dp-switch'); if(!s)return; s.style.display = (s.style.display==='block'?'none':'block');})();">⚙</span>
    </div>

    <!-- Optional table switcher (hidden by default—click gear) -->
    <form id="dp-switch" class="dp-switch" method="get" action="/DataPoint/">
        <label for="table" style="margin-right:6px;">Table:</label>
        <select name="table" id="table" onchange="this.form.submit()">
            <?php foreach ($allowedTables as $t): ?>
                <option value="<?php echo htmlspecialchars($t); ?>"<?php if ($t === $dataTable) echo ' selected'; ?>>
                    <?php echo function_exists('datapoint_table_title') ? htmlspecialchars(datapoint_table_title($t)) : htmlspecialchars($t); ?>
                </option>
            <?php endforeach; ?>
        </select>
        <noscript><button type="submit" class="dp-button">Open</button></noscript>
    </form>

    <!-- Top controls: Rows-per-page + Export + Distance sort -->
    <div class="dp-controls">
        <div class="left">
            <form method="get" action="/DataPoint/">
                <input type="hidden" name="table" value="<?php echo htmlspecialchars($dataTable); ?>">
                <label for="rows">Rows:</label>
                <select id="rows" name="rows" onchange="this.form.submit()">
                    <?php foreach ([10,25,50,100] as $opt): ?>
                        <option value="<?php echo $opt; ?>"<?php if ($rows === $opt) echo ' selected'; ?>>
                            <?php echo $opt; ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <noscript><button class="dp-button" type="submit">Apply</button></noscript>
            </form>
            <?php if ($canDistance): ?>
                <a class="dp-button" href="/DataPoint/?table=<?php echo urlencode($dataTable); ?>&rows=<?php echo (int)$rows; ?>&sort=distance&ad=<?php echo htmlspecialchars($nextAd); ?>">
                    <?php echo htmlspecialchars($distLabel); ?>
                </a>
            <?php endif; ?>
        </div>
        <div class="right">
            <a class="dp-button" href="/DataPoint/export.php?table=<?php echo urlencode($dataTable); ?>">Export CSV</a>
        </div>
    </div>

    <div id="data-view" class="dp-mte">
        <?php $tabledit->do_it(); ?>
    </div>
</div>

</body>
</html>

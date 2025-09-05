<?php
/**
 * Data Point (refactor peel 1.1 — default to edtb_systems + curated columns)
 */

session_start();

// Core includes (paths kept portable)
require_once dirname(__DIR__) . '/source/config.inc.php';
require_once dirname(__DIR__) . '/source/config_ini.inc.php';
require_once dirname(__DIR__) . '/source/functions.php';

// Header UI (no Theme class)
require_once dirname(__DIR__) . '/style/Header.php';

// Schema + per-table config
require_once __DIR__ . '/Schema.php';
require_once __DIR__ . '/Tables.php';

// --- Vendor compatibility shim: setData() ---
if (!function_exists('setData')) {
    function setData($first, $second = null, $third = null) {
        if (is_array($first)) {
            $arr = $first; $key = $second; $def = $third;
            return array_key_exists($key, $arr) ? $arr[$key] : $def;
        }
        $key = (string)$first; $def = $second; $method = strtoupper((string)($third ?? 'REQUEST'));
        switch ($method) {
            case 'GET':  $src = $_GET;  break;
            case 'POST': $src = $_POST; break;
            default:     $src = $_REQUEST;
        }
        return array_key_exists($key, $src) ? $src[$key] : $def;
    }
}
// --- end shim ---

// Vendor table editor (unchanged)
require_once __DIR__ . '/Vendor/MySQL_table_edit/mte.php';

// DB handle + vendor globals
/** @var mysqli $mysqli */
global $mysqli, $server, $user, $pwd, $db, $settings;

/* ---- Ensure vendor DB globals exist for MySQLtabledit ---- */
$server = $server ?? ($settings['db_host'] ?? (defined('DB_HOST') ? DB_HOST : '127.0.0.1'));
if (!empty($settings['db_port'])) { $server .= ':' . (int)$settings['db_port']; }
$user   = $user ?? ($settings['db_user'] ?? (defined('DB_USER') ? DB_USER : 'root'));
$pwd    = $pwd  ?? ($settings['db_pass'] ?? (defined('DB_PASS') ? DB_PASS : ''));
$db     = $db   ?? ($settings['db_name'] ?? (defined('DB_NAME') ? DB_NAME : ''));

if (!($mysqli instanceof mysqli)) {
    $mysqli = @new mysqli($server, $user, $pwd, $db);
} else {
    $res = @$mysqli->query('SELECT DATABASE()');
    $row = $res ? $res->fetch_row() : null;
    if ($res) { $res->close(); }
    if (!$row || !$row[0]) { @($db !== '' ? $mysqli->select_db($db) : null); }
}
/* ---- end DB globals assurance ---- */

// ---- Resolve allowed tables & default selection ----
$allowedTables = datapoint_table_whitelist($mysqli);

// Prefer edtb_systems first in the dropdown order (if it exists)
usort($allowedTables, function($a, $b) {
    if ($a === 'edtb_systems') return -1;
    if ($b === 'edtb_systems') return 1;
    return strcmp($a, $b);
});

$requested = isset($_GET['table']) ? (string)$_GET['table'] : '';
$preferredDefault = in_array('edtb_systems', $allowedTables, true) ? 'edtb_systems' : ($allowedTables[0] ?? '');
$dataTable = in_array($requested, $allowedTables, true) ? $requested : $preferredDefault;

// ---- Build columns + labels (auto) ----
list($allFields, $autoLabels) = datapoint_get_column_labels($mysqli, $dataTable);

// ---- Apply per-table overrides (curated list + nice labels) ----
$config = function_exists('datapoint_config_for_table') ? datapoint_config_for_table($dataTable) : [];
if (!empty($config['list'])) {
    // Keep the order defined in config, but only include fields that exist
    $fieldsInListView = array_values(array_filter($config['list'], function($f) use ($allFields) {
        return in_array($f, $allFields, true);
    }));
    if (!$fieldsInListView) {
        $fieldsInListView = $allFields; // fallback
    }
} else {
    // Heuristic fallback: show up to first 7 columns
    $fieldsInListView = array_slice($allFields, 0, 7);
}

$showText = $autoLabels;
if (!empty($config['labels'])) {
    // Merge overrides (config wins)
    $showText = array_replace($showText, $config['labels']);
}

// ---- Configure vendor ----
$tabledit = new MySQLtabledit();
$tableMap = [$dataTable => $dataTable];

$tabledit->table               = $dataTable;
$tabledit->links_to_db         = $tableMap;
$tabledit->linksToDb           = $tableMap;
$tabledit->primary_key         = 'id';
$tabledit->primaryKey          = 'id';
$tabledit->fields_in_list_view = $fieldsInListView;
$tabledit->fieldsInListView    = $fieldsInListView;
$tabledit->show_text           = $showText;
$tabledit->showText            = $showText;
$tabledit->num_rows_list_view  = 10;
$tabledit->numRowsListView     = 10;
$tabledit->url_base            = 'Vendor/MySQL_table_edit/';
$tabledit->urlBase             = 'Vendor/MySQL_table_edit/';
$tabledit->url_script          = '/DataPoint';
$tabledit->urlScript           = '/DataPoint';

// Optional: default order (if your vendor build supports it)
// if (!empty($config['order_by'])) { $tabledit->order_by = $config['order_by']; }

// ---- Page Output ----
$header = new Header();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Data Point</title>
    <?php
    if (method_exists($header, 'displayCss')) {
        $header->displayCss();
    } else {
        echo '<link rel="stylesheet" href="/style/style.css">';
    }
    ?>
</head>
<body>
<?php if (method_exists($header, 'displayHeader')) { $header->displayHeader('Data Point'); } ?>

<div class="container" style="padding: 10px 15px;">
    <form method="get" action="/DataPoint/" style="margin-bottom:12px;">
        <label for="table">Table:</label>
        <select name="table" id="table" onchange="this.form.submit()">
            <?php foreach ($allowedTables as $t): ?>
                <option value="<?php echo htmlspecialchars($t); ?>"<?php if ($t === $dataTable) echo ' selected'; ?>>
                    <?php echo htmlspecialchars($t); ?>
                </option>
            <?php endforeach; ?>
        </select>
        <noscript><button type="submit">Open</button></noscript>
    </form>

    <div id="data-view">
        <?php $tabledit->do_it(); ?>
    </div>
</div>

</body>
</html>

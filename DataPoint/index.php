<?php
/**
 * Data Point (refactor peel 1 — no Theme dependency)
 *
 * - Uses Schema.php for fields + friendly labels
 * - Validates requested table against whitelist
 * - Wires Vendor/MySQL_table_edit without editing vendor code
 */

session_start();

// Core includes (paths kept portable)
require_once dirname(__DIR__) . '/source/config.inc.php';
require_once dirname(__DIR__) . '/source/config_ini.inc.php';
require_once dirname(__DIR__) . '/source/functions.php';

// Header UI (no Theme class)
require_once dirname(__DIR__) . '/style/Header.php';

// Schema helpers for Data Point
require_once __DIR__ . '/Schema.php';

// Vendor table editor (unchanged)
require_once __DIR__ . '/Vendor/MySQL_table_edit/mte.php';

// DB handle
/** @var mysqli $mysqli */
global $mysqli;

// Resolve requested table and validate against whitelist
$allowedTables = datapoint_table_whitelist($mysqli);
$requested     = isset($_GET['table']) ? (string)$_GET['table'] : '';
$dataTable     = in_array($requested, $allowedTables, true) ? $requested : ($allowedTables[0] ?? 'edtb_systems');

// Build column list + friendly labels
list($fieldsInListView, $showText) = datapoint_get_column_labels($mysqli, $dataTable);

// Configure MySQLtabledit (set both snake_case and camelCase props for compatibility)
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

// (Optional) default skip list; adjust later via per-table config
$tabledit->skip = [];

// ---- Page Output ----
$header = new Header();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Data Point</title>
    <?php
    // Prefer project CSS helper if available; otherwise fallback to style.css
    if (method_exists($header, 'displayCss')) {
        $header->displayCss();
    } else {
        echo '<link rel="stylesheet" href="/style/style.css">';
    }
    ?>
</head>
<body>
<?php
// Render site header if available
if (method_exists($header, 'displayHeader')) {
    $header->displayHeader('Data Point');
}
?>

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
        <?php
        // Render the vendor UI
        $tabledit->do_it();
        ?>
    </div>
</div>

</body>
</html>

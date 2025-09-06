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

/* ---- UI header (no Theme dependency) ---- */
require_once dirname(__DIR__) . '/style/Header.php';



require_once __DIR__ . '/Bootstrap.php';
require_once __DIR__ . '/TableEditFactory.php';

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
    
<?php include __DIR__ . '/partials/assets.php'; ?>
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
    <?php include __DIR__ . '/partials/quickfilters.php'; ?>


    <?php include __DIR__ . '/partials/table.php'; ?>

</div>

</body>
</html>

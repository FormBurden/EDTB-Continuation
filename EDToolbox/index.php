<?php
// EDToolbox/index.php — render ED ToolBox inside the standard page shell
declare(strict_types=1);

$ROOT = dirname(__DIR__);

/**
 * Use the same pattern as the front page:
 *   1) create Header
 *   2) display header (opens wrappers + sidebar)
 *   3) render ED ToolBox partial
 *   4) display footer (closes wrappers)
 */
require_once $ROOT . '/style/Theme.php';

$header = new Header();
$header->pageTitle = 'ED ToolBox';
$header->displayHeader();
?>

<?php require __DIR__ . '/partial.php'; ?>

<link rel="stylesheet" href="/EDToolbox/css/edtoolbox.css">
<script>
  window.EDTBX_CFG = {
    api: "/get/getData.php",
    request: "0"
  };
</script>
<script src="/EDToolbox/js/edtoolbox.js" defer></script>

<?php
$footer = new Footer();
$footer->displayFooter();

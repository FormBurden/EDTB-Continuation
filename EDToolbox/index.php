<?php
// EDToolbox/index.php — canonical ED ToolBox page shell (no dynamic loader)
declare(strict_types=1);

$ROOT = dirname(__DIR__);

// Optional: include your global header wrapper if your app expects it.
$header = $ROOT . '/style/Header.php';
if (is_file($header)) require_once $header;

// Request value used by the API
$request = isset($_GET['request']) ? (string)$_GET['request'] : '0';

// API endpoint consumed by edtoolbox.js (JSON: si_name, si_detailed, si_stations)
$edtbx_api = '/get/getData.php';

// Render the static partial markup
require __DIR__ . '/partial.php';
?>
<link rel="stylesheet" href="/EDToolbox/css/edtoolbox.css">
<script>
  window.EDTBX_CFG = {
    api: "<?php echo $edtbx_api; ?>",
    request: "<?php echo htmlspecialchars($request, ENT_QUOTES); ?>"
  };
</script>
<script src="/EDToolbox/js/edtoolbox.js" defer></script>
<?php
// Optional footer include if you have one.
// $footer = $ROOT . '/style/footer.php';
// if (is_file($footer)) require $footer;

<?php
// EDToolbox/index.php — canonical page using the app’s standard wrappers
declare(strict_types=1);

$ROOT = dirname(__DIR__);

/**
 * IMPORTANT:
 * Do NOT include head.php/body_open.php/top_panel.php directly here.
 * Your build expects them to be orchestrated by style/Header.php,
 * which provides the required $this context. Including head.php
 * directly causes "Using $this when not in object context".
 */
require_once $ROOT . '/style/Header.php';
?>
<div class="right_panel">
  <div id="content" class="content">
    <?php require __DIR__ . '/partial.php'; ?>
  </div>
</div>

<link rel="stylesheet" href="/EDToolbox/css/edtoolbox.css">
<script>
  window.EDTBX_CFG = {
    api: "/get/getData.php",
    request: "0"
  };
</script>
<script src="/EDToolbox/js/edtoolbox.js" defer></script>

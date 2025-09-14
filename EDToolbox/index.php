<?php
// EDToolbox/index.php — single-view (no alt settings branch)
// Renders the main ED Toolbox view and loads its JS unconditionally.

$request = isset($_GET['request']) ? htmlspecialchars($_GET['request'], ENT_QUOTES, 'UTF-8') : '';

// Shared header (navigation, cog, etc.)
require_once __DIR__ . '/../style/Header.php';
?>
<link rel="stylesheet" href="css/edtoolbox.css?v=1">

<main id="edtbx-root" data-request="<?php echo $request; ?>">
  <header class="edtbx-header">
    <h1>ED TOOLBOX</h1>
    <div class="edtbx-actions">
      <button id="edtbx-refresh" type="button">Refresh</button>
    </div>
  </header>

  <section class="edtbx-section">
    <h2 class="edtbx-h2">System</h2>
    <div id="edtbx-system-title" class="edtbx-title">—</div>
    <div id="edtbx-system-info" class="edtbx-kv-grid"></div>
  </section>

  <section class="edtbx-section">
    <h2 class="edtbx-h2">Stations</h2>
    <div class="edtbx-table-wrap">
      <table id="edtbx-stations" class="edtbx-table">
        <thead></thead>
        <tbody></tbody>
      </table>
    </div>
  </section>

  <section class="edtbx-section">
    <h2 class="edtbx-h2">Logs</h2>
    <pre id="edtbx-log" class="edtbx-log">Waiting…</pre>
  </section>
</main>

<script src="js/edtoolbox.js?v=1" defer></script>

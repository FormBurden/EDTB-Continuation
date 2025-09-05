<?php
// EDToolbox partial markup (no <html>/<head>), for in-page injection
// Path: /EDToolbox/partial.php
$request = isset($_GET['request']) ? preg_replace('/[^0-9]/', '', $_GET['request']) : '0';
?>
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

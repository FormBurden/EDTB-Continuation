<?php
// EDToolbox — isolated ED TOOLBOX tab (Linux-native)
// Path: /EDToolbox/index.php
// Minimal, self-contained view that fetches data from /get/getData.php
// Usage: visit /EDToolbox/?request=0 (default 0)
$request = isset($_GET['request']) ? preg_replace('/[^0-9]/', '', $_GET['request']) : '0';
?><!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>ED Toolbox</title>
  <link rel="stylesheet" href="css/edtoolbox.css?v=1">
</head>
<body>
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
  <script>
    window.EDTBX_CFG = {
      api: "/get/getData.php"
    };
  </script>
  <script src="js/edtoolbox.js?v=1" defer></script>
</body>
</html>

<?php
// EDToolbox — isolated ED TOOLBOX tab (Linux-native)
// Path: /EDToolbox/index.php
// Minimal, self-contained view that fetches data from /get/getData.php
// Usage: visit /EDToolbox/?request=0 (default 0)
$request = isset($_GET['request']) ? preg_replace('/[^0-9]/', '', $_GET['request']) : '0';
$to = isset($_GET['to']) ? preg_replace('/[^a-z]/', '', strtolower($_GET['to'])) : '';
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
<?php if ($to === 'settings'): ?>
  <main class="edtbx-settings">
    <header class="edtbx-header">
      <h1>ED TOOLBOX — Settings</h1>
    </header>

    <section class="edtbx-section">
      <h2 class="edtbx-h2">General</h2>
      <div class="edtbx-table-wrap">
        <table class="edtbx-table">
          <tbody>
            <tr>
              <td class="edtbx-k">Admin Settings</td>
              <td class="edtbx-v">Open administration & database tools.</td>
              <td class="edtbx-v">
                <a href="/Admin/index.php" data-push="true" title="Open Admin">Open</a>
              </td>
            </tr>
            <tr>
              <td class="edtbx-k">System &amp; Station Data</td>
              <td class="edtbx-v">Download/update cached data from APIs.</td>
              <td class="edtbx-v">
                <a href="/action/updateAPIdata.php" target="_blank" rel="noopener" title="Run update now">Run now</a>
              </td>
            </tr>
            <tr>
            <td class="edtbx-k">Logs: Recorder / Player</td>
            <td class="edtbx-v">View and manage session logs.</td>
            <td class="edtbx-v">
              <a id="link-logs-player"
                href="#"
                style="pointer-events:none;opacity:.55"
                data-template="/Log/index.php?system={SYSTEM}"
                title="Open Log Player">Player</a>
              &nbsp;·&nbsp;
              <a id="link-logs-recorder"
                href="#"
                style="pointer-events:none;opacity:.55"
                data-template="/Log/add_log.php?system={SYSTEM}"
                title="Open Log Recorder">Recorder</a>
            </td>

          </tbody>
        </table>
      </div>
    </section>

    <section class="edtbx-section">
      <a href="/EDToolbox/" title="Back to ED Toolbox">← Back to ED Toolbox</a>
    </section>
    <script>
    (function () {
      function enableLink(a, systemName) {
        if (!a) return;
        var href = a.getAttribute('data-template').replace('{SYSTEM}', encodeURIComponent(systemName));
        a.setAttribute('href', href);
        a.style.pointerEvents = 'auto';
        a.style.opacity = '';
      }

      // Pull current system from status endpoint
      fetch('/get/getData_status.php', {credentials: 'same-origin'})
        .then(function (r) { return r.json(); })
        .then(function (j) {
          // common field names seen across EDTB status payloads
          var sys =
            (j.current_system && (j.current_system.name || j.current_system.system)) ||
            j.system_name ||
            j.system ||
            (j.last_system && j.last_system.name);

          if (sys && typeof sys === 'string' && sys.length) {
            enableLink(document.getElementById('link-logs-player'), sys);
            enableLink(document.getElementById('link-logs-recorder'), sys);
          }
        });
    })();
    </script>

  </main>
<?php else: ?>
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
<?php endif; ?>

  <script>
    window.EDTBX_CFG = {
      api: "/get/getData.php"
    };
  </script>
  <?php if (empty($to) || $to !== 'settings'): ?>
    <script src="js/edtoolbox.js?v=1" defer></script>
  <?php endif; ?>

</body>
</html>

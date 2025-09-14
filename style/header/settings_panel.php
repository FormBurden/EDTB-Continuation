<?php
/**
 * Header -> Settings dropdown (original behavior)
 * - Settings  → /Admin/index.php  (tabbed page: General / Display / Audio / Paths)
 * - Download → /action/updateAPIdata.php  (opens updater)
 * - Logs     → /Log/index.php?system=<current system>
 */
?>
<div id="settings" class="rightpanel" style="display:none">
  <a id="sp-settings" href="/Admin/index.php" title="Open Settings">Settings</a>
  <a id="sp-update"  href="/action/updateAPIdata.php" target="_blank" rel="noopener" title="Download/Update cached data">Download System &amp; Station Data</a>
  <a id="sp-logs"    href="#" data-template="/Log/index.php?system={SYSTEM}" title="Open Logs (Player)">Logs Recorder / Player</a>
</div>

<script>
(function () {
  function setLogsHref(systemName) {
    var a = document.getElementById('sp-logs');
    if (!a || !systemName) return;
    a.href = a.getAttribute('data-template').replace('{SYSTEM}', encodeURIComponent(systemName));
  }
  fetch('/get/getData_status.php', {credentials: 'same-origin'})
    .then(function (r) { return r.json(); })
    .then(function (j) {
      var sys =
        (j.current_system && (j.current_system.name || j.current_system.system)) ||
        j.system_name || j.system || '';
      if (sys) setLogsHref(sys);
    });
})();
</script>

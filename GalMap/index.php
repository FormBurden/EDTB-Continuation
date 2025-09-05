<?php
/**
 * Galaxy Map - slim refactor wrapper
 * Uses ED3D vendor + our JSON endpoint (getMapPoints.json.php)
 */

require_once __DIR__ . '/../style/Theme.php'; // brings in Header & Footer
$header = new Header();
$header->pageTitle = 'Galaxy Map';
$header->displayHeader();
?>
<link rel="stylesheet" href="GalMap/Vendor/ED3D-Galaxy-Map/css/styles.css" />

<style>
  /* keep it simple: map fills the remaining viewport below the top panel */
  #galmap-wrap { padding: 10px 12px 0; }
  #ed3dmap { width: 100%; height: calc(100vh - 240px); position: relative; }
  #loader { position: absolute; top: 12px; right: 16px; z-index: 9999; display: none; }
  .gm-controls { display: flex; flex-wrap: wrap; gap: 12px; align-items: center; margin-bottom: 10px; }
  .gm-controls label { margin-right: 6px; }
  .gm-badge { opacity: 0.85; padding: 2px 6px; border-radius: 4px; background: #1b2a30; }
  .gm-hr { margin: 8px 0 12px; border: 0; border-top: 1px solid #24373d; }
</style>

<div id="galmap-wrap">
  <div class="gm-controls">
    <span class="gm-badge">Data source: getMapPoints.json.php</span>

    <label><input type="checkbox" id="gm_visited"> visited only</label>
    <label><input type="checkbox" id="gm_bookmarked"> bookmarked only</label>

    <label for="gm_limit">limit</label>
    <input id="gm_limit" type="number" min="1" max="50000" value="15000" style="width: 100px;">

    <button id="gm_apply" type="button">Apply</button>
    <span id="gm_status" class="gm-badge" style="display:none;"></span>
  </div>
  <hr class="gm-hr">

  <div id="ed3dmap"></div>
  <div id="loader">Loading…</div>

  <!-- ED3D distance HUD elements (expected by vendor script) -->
  <span id="curx" style="display:none">0</span>
  <span id="cury" style="display:none">0</span>
  <span id="curz" style="display:none">0</span>
</div>

<!-- Dependencies: jQuery (site-wide), Three.js, ED3D map core -->
<script src="/source/Vendor/jquery-2.2.0.min.js"></script>
<script src="/source/Vendor/three.min.js"></script>
<script src="GalMap/Vendor/ED3D-Galaxy-Map/js/ed3dmap.js"></script>

<script>
  (function () {
    // Base path for ED3D vendor (it lazy-loads its own components from this base)
    var basePath = 'GalMap/Vendor/ED3D-Galaxy-Map/';

    // Build JSON URL from controls
    function jsonURL() {
      var u = new URL('GalMap/getMapPoints.json.php', window.location.origin);
      var limit = Math.max(1, Math.min(50000, parseInt(document.getElementById('gm_limit').value || '15000', 10)));
      u.searchParams.set('limit', String(limit));

      if (document.getElementById('gm_visited').checked) {
        u.searchParams.set('visited_only', '1');
      }
      if (document.getElementById('gm_bookmarked').checked) {
        u.searchParams.set('bookmarked_only', '1');
      }
      return u.pathname + u.search;
    }

    // Initialize map once the page is ready
    function initMap() {
      // First render
      Ed3d.init({
        container: 'ed3dmap',
        basePath: basePath,
        jsonPath: jsonURL(),
        withHudPanel: true,
        startAnim: true
      });
    }

    // Rebuild map with new data source (ED3D supports a rebuild call)
    function applyFilters() {
      var url = jsonURL();
      Ed3d.jsonPath = url;
      if (typeof Ed3d.rebuild === 'function') {
        Ed3d.rebuild();
        var s = document.getElementById('gm_status');
        s.textContent = 'Applied: ' + url;
        s.style.display = '';
        setTimeout(function(){ s.style.display = 'none'; }, 2000);
      }
    }

    // Hook up UI
    document.getElementById('gm_apply').addEventListener('click', applyFilters);

    // Kick off
    initMap();
  })();
</script>

<?php
$footer = new Footer();
$footer->displayFooter();

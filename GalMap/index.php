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

<link rel="stylesheet" href="Vendor/ED3D-Galaxy-Map/css/styles.css" />

<style>
  /* keep it simple: map fills the remaining viewport below the top panel */
  #galmap-wrap { margin-top: 88px; padding: 10px 12px 0; }
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
    <!-- Center by system name or coordinates + radius -->
    <label for="gm_center_system" style="margin-left: 8px;">center system</label>
    <input id="gm_center_system" type="text" placeholder="e.g., Sol" style="width: 160px;">

    <span style="margin-left:8px;">or coords</span>
    <label for="gm_cx">x</label>
    <input id="gm_cx" type="number" step="0.0001" style="width: 90px;">
    <label for="gm_cy">y</label>
    <input id="gm_cy" type="number" step="0.0001" style="width: 90px;">
    <label for="gm_cz">z</label>
    <input id="gm_cz" type="number" step="0.0001" style="width: 90px;">

    <label for="gm_radius" style="margin-left:8px;">maxdistance</label>
    <input id="gm_radius" type="number" min="1" max="50000" step="1" value="200" style="width: 110px;">


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
<script src="../source/Vendor/jquery-2.2.0.min.js"></script>
<script src="../source/Vendor/three.min.js"></script>
<script src="Vendor/ED3D-Galaxy-Map/js/ed3dmap.js"></script>

<script>
  (function () {
    // Base path for ED3D vendor (it lazy-loads its own components from this base)
    var basePath = 'Vendor/ED3D-Galaxy-Map/';

    // Build JSON URL from controls
    function jsonURL() {
      var u = new URL('getMapPoints.json.php', window.location.href);
      var limit = Math.max(1, Math.min(50000, parseInt(document.getElementById('gm_limit').value || '15000', 10)));
      u.searchParams.set('limit', String(limit));

      // Optional center inputs
      var cs = document.getElementById('gm_center_system').value.trim();
      var cx = document.getElementById('gm_cx').value.trim();
      var cy = document.getElementById('gm_cy').value.trim();
      var cz = document.getElementById('gm_cz').value.trim();
      var rad = document.getElementById('gm_radius').value.trim();

      if (cs !== '') {
        u.searchParams.set('center_system', cs);
      }
      if (cx !== '' && cy !== '' && cz !== '') {
        u.searchParams.set('centerX', cx);
        u.searchParams.set('centerY', cy);
        u.searchParams.set('centerZ', cz);
      }
      if (rad !== '') {
        u.searchParams.set('maxdistance', rad);
      }



      if (document.getElementById('gm_visited').checked) {
        u.searchParams.set('visited_only', '1');
      }
      if (document.getElementById('gm_bookmarked').checked) {
        u.searchParams.set('bookmarked_only', '1');
      }
            // Optional center + radius
            var cx = document.getElementById('gm_cx').value.trim();
      var cy = document.getElementById('gm_cy').value.trim();
      var cz = document.getElementById('gm_cz').value.trim();
      var rad = document.getElementById('gm_radius').value.trim();

      if (cx !== '' && cy !== '' && cz !== '') {
        u.searchParams.set('centerX', cx);
        u.searchParams.set('centerY', cy);
        u.searchParams.set('centerZ', cz);
      }
      if (rad !== '') {
        u.searchParams.set('maxdistance', rad);
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
    // Lookup coords for a system name and fill x/y/z inputs
    function lookupCenterSystem(name, cb) {
      if (!name) { if (cb) cb(false); return; }

      // Ask our JSON feed for the named system; 1 ly radius includes the system itself
      var url = new URL('getMapPoints.json.php', window.location.href);
      url.searchParams.set('limit', '1');
      url.searchParams.set('center_system', name);
      url.searchParams.set('maxdistance', '1');

      fetch(url.toString(), { credentials: 'same-origin' })
        .then(function (r) { return r.json(); })
        .then(function (data) {
          if (data && data.systems && data.systems.length) {
            var c = data.systems[0].coords || {};
            if (typeof c.x !== 'undefined' && typeof c.y !== 'undefined' && typeof c.z !== 'undefined') {
              document.getElementById('gm_cx').value = c.x;
              document.getElementById('gm_cy').value = c.y;
              document.getElementById('gm_cz').value = c.z;
              if (cb) cb(true, c);
              return;
            }
          }
          if (cb) cb(false);
        })
        .catch(function () { if (cb) cb(false); });
    }
    // ----- center-system -> coords lookup (fills x/y/z, then cb) -----
    function lookupCenterSystem(name, cb) {
      if (!name) { if (cb) cb(false); return; }
      var url = new URL('getMapPoints.json.php', window.location.href);
      url.searchParams.set('limit', '1');
      url.searchParams.set('center_system', name);
      url.searchParams.set('maxdistance', '1');

      fetch(url.toString(), { credentials: 'same-origin' })
        .then(function (r) { return r.json(); })
        .then(function (data) {
          if (data && data.systems && data.systems.length) {
            var c = data.systems[0].coords || {};
            if (typeof c.x !== 'undefined' && typeof c.y !== 'undefined' && typeof c.z !== 'undefined') {
              document.getElementById('gm_cx').value = c.x;
              document.getElementById('gm_cy').value = c.y;
              document.getElementById('gm_cz').value = c.z;
              if (cb) cb(true, c);
              return;
            }
          }
          if (cb) cb(false);
        })
        .catch(function () { if (cb) cb(false); });
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
    // APPLY: if center_system is set and any coord is blank, resolve name -> coords first
    document.getElementById('gm_apply').addEventListener('click', function (ev) {
      var cs = document.getElementById('gm_center_system').value.trim();
      var cx = document.getElementById('gm_cx').value.trim();
      var cy = document.getElementById('gm_cy').value.trim();
      var cz = document.getElementById('gm_cz').value.trim();

      if (cs !== '' && (cx === '' || cy === '' || cz === '')) {
        lookupCenterSystem(cs, function () { applyFilters(); });
      } else {
        applyFilters();
      }
    });

    // Pressing Enter in the "center system" box triggers the same flow
    document.getElementById('gm_center_system').addEventListener('keydown', function (e) {
      if (e.key === 'Enter') {
        e.preventDefault();
        document.getElementById('gm_apply').click();
      }
    });

    // Auto-fill coords when a center system is entered
    var csInput = document.getElementById('gm_center_system');

    // On leaving the field, just populate x/y/z (no rebuild)
    csInput.addEventListener('blur', function () {
      var name = csInput.value.trim();
      lookupCenterSystem(name);
    });

    // On Enter: populate and rebuild
    csInput.addEventListener('keydown', function (ev) {
      if (ev.key === 'Enter') {
        ev.preventDefault();
        var name = csInput.value.trim();
        lookupCenterSystem(name, function () {
          applyFilters();
        });
      }
    });

    // Kick off
    initMap();
  })();
</script>

<?php
$footer = new Footer();
$footer->displayFooter();

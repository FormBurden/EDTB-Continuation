<?php
declare(strict_types=1);
$root = dirname(__DIR__);
require_once $root . '/style/Header.php';
require_once __DIR__ . '/../style/Footer.php';

$header = new Header();
$pageTitle = 'Galaxy Map';
$header->displayHeader();
?>
<div id="galmap-page" class="container" style="padding:30px;">
  <h2>Galaxy Map (JSON-backed)</h2>

  <form id="galmap-form" class="form-inline" style="margin-bottom:12px; position:relative; z-index:1000006;">
  <button type="button" id="center_current" class="btn btn-sm">Center: Current</button>
    <div style="display:flex; gap:12px; flex-wrap:wrap;">
      <label>limit <input id="limit" name="limit" type="number" value="15000" style="width:90px;"></label>
      <label>maxdistance <input id="maxdistance" name="maxdistance" type="number" value="50" style="width:90px;"></label>

      <label>center system <input id="center_system" name="center_system" type="text" placeholder="Sol" list="system-suggest" style="width:160px;"></label>
      <label>x <input id="centerX" name="centerX" type="text" style="width:80x;"></label>
      <label>y <input id="centerY" name="centerY" type="text" style="width:80px;"></label>
      <label>z <input id="centerZ" name="centerZ" type="text" style="width:80px;"></label>

      <label><input id="visited_only" name="visited_only" type="checkbox" value="1"> visited only</label>
      <label><input id="bookmarked_only" name="bookmarked_only" type="checkbox" value="1"> bookmarked only</label>

      <button type="submit">Apply</button>
    </div>
  </form>

  <div>
    <strong>Results:</strong> <span id="results-count">0</span>
    <div id="results-list" style="margin-top:6px; font-size: 0.95em; color:#ccc;">(nothing yet)</div>
  </div>

  <hr>
  <!-- ED3D map viewport -->
  <div id="ed3d-wrapper" style="position:relative; height: calc(100vh - 180px); min-height: 520px; margin-top: 8px;">
    <div id="ed3dmap" class="edmap"></div>
  </div>
  <p style="font-size:0.9em;opacity:.8">Data source: <code>/GalMap/getMapPoints.json.php</code> (works even without player logs; user tables are optional joins).</p>
</div>
<link rel="stylesheet" href="/GalMap/Vendor/ED3D-Galaxy-Map/css/styles.css">
<script src="/source/Vendor/three.min.js"></script>
<script src="/GalMap/Vendor/ED3D-Galaxy-Map/js/ed3dmap.js"></script>
<script src="/GalMap/js/galmap.js"></script>

<?php
$footer = new Footer();
$footer->displayFooter();


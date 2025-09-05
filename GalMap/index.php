<?php
declare(strict_types=1);
$root = dirname(__DIR__);
require_once $root . '/style/Header.php';
require_once __DIR__ . '/../style/Footer.php';

$header = new Header();
$pageTitle = 'Galaxy Map';
$header->displayHeader();
?>
<div class="container" style="padding:12px;">
  <h2>Galaxy Map (JSON-backed)</h2>

  <form id="galmap-form" class="form-inline" style="margin-bottom:12px;">
    <div style="display:flex; gap:12px; flex-wrap:wrap;">
      <label>limit <input id="limit" name="limit" type="number" value="15000" style="width:90px;"></label>
      <label>maxdistance <input id="maxdistance" name="maxdistance" type="number" value="50" style="width:90px;"></label>

      <label>center system <input id="center_system" name="center_system" type="text" placeholder="Sol" style="width:160px;"></label>
      <label>x <input id="centerX" name="centerX" type="text" style="width:80px;"></label>
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
  <p style="font-size:0.9em;opacity:.8">Data source: <code>/GalMap/getMapPoints.json.php</code> (works even without player logs; user tables are optional joins).</p>
</div>
<script src="/GalMap/js/galmap.js"></script>
<?php
$footer = new Footer();
$footer->displayFooter();


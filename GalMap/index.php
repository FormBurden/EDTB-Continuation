<?php
declare(strict_types=1);
$root = dirname(__DIR__);
require_once $root . '/style/Header.php';
require_once $root . '/style/Footer.php';

$header = new Header();
$pageTitle = 'Galaxy Map';
$header->displayHeader();
?>
<div class="galmap-wrap">
  <h1 class="galmap-title">Galaxy Map</h1>

  <form id="galmap-form" class="gm-controls" action="#" method="get" autocomplete="off">
    <div class="row">
      <label>Center system</label>
      <input type="text" id="center_system" name="center_system" list="system_names" placeholder="e.g. Sol">
      <datalist id="system_names"></datalist>

      <label>Max distance</label>
      <input type="number" id="maxdistance" name="maxdistance" step="1" value="50" min="1" max="100000">

      <label>Limit</label>
      <input type="number" id="limit" name="limit" step="1" value="15000" min="1" max="15000">

      <button type="button" id="center_current" class="btn btn-sm">Center: Current</button>
    </div>

    <div class="row">
      <label>Color by</label>
      <select id="color_by" name="color_by">
        <option value="none">None</option>
        <option value="allegiance">Allegiance</option>
        <option value="government">Government</option>
        <option value="economy">Economy</option>
        <option value="security">Security</option>
      </select>

      <label><input type="checkbox" id="visited_only" name="visited_only" value="1"> Visited only</label>
      <label><input type="checkbox" id="bookmarked_only" name="bookmarked_only" value="1"> Bookmarked only</label>

      <button type="submit" class="btn">Apply</button>
    </div>

    <!-- Center coords (auto-filled from suggestions or exact lookup) -->
    <div class="row coords">
      <label>X</label><input type="number" id="centerX" name="centerX" step="0.0001" placeholder="X">
      <label>Y</label><input type="number" id="centerY" name="centerY" step="0.0001" placeholder="Y">
      <label>Z</label><input type="number" id="centerZ" name="centerZ" step="0.0001" placeholder="Z">
    </div>
  </form>

  <div class="gm-main">
    <div class="left-panel">
      <div class="legend" id="legend"></div>
      <div class="results">
        <div class="results-header">
          Results: <span id="results-count">0</span>
        </div>
        <div id="results-list" class="results-list">(nothing yet)</div>
      </div>
    </div>
    <div class="map-panel">
      <div id="ed3dmap" class="ed3dmap"></div>
    </div>
  </div>
</div>

<link rel="stylesheet" href="/GalMap/Vendor/ED3D-Galaxy-Map/css/styles.css">
<script src="/source/Vendor/three.min.js"></script>
<!-- IMPORTANT: use the library build, not the old wrapper -->
<script src="/source/Vendor/ED3D-Galaxy-Map/js/ed3dmap.js"></script>
<!-- <link rel="stylesheet" href="/source/Vendor/ED3D-Galaxy-Map/css/styles.css" type="text/css" /> -->
<script src="/GalMap/js/galmap.js"></script>
<script>
/* Galaxy Map: default-hide overlays and tooltips, and close on map click */
$(function () {
  // Hide all modal panels and tooltips on load
  $('#addlog,#addBm,#distance,#search_system,.tooltip,#map_legend2').hide();

  // Clicking the map canvas or its panel closes any open overlays
  $('#ed3dmap, .map-panel').on('mousedown click', function() {
    $('#addlog,#addBm,#distance,#search_system,.tooltip,#map_legend2').fadeOut('fast');
  });
});
</script>


<?php
$footer = new Footer();
$footer->displayFooter();

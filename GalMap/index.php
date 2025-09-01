<?php
declare(strict_types=1);

// Sanitize optional query params (not strictly needed for ED3D init,
// but kept here if you want to use them later in JS)
$gx = isset($_GET['x']) && is_numeric($_GET['x']) ? (float)$_GET['x'] : 0.0;
$gy = isset($_GET['y']) && is_numeric($_GET['y']) ? (float)$_GET['y'] : 0.0;
$gz = isset($_GET['z']) && is_numeric($_GET['z']) ? (float)$_GET['z'] : 0.0;
$zoom = isset($_GET['zoom']) && is_numeric($_GET['zoom']) ? (float)$_GET['zoom'] : 1.0;

// Nothing in this file does arithmetic with strings anymore.
// All map data is loaded via ED3D from getMapPoints.json.php.
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title>EDTB — Galaxy Map</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link rel="stylesheet" href="Vendor/ED3D-Galaxy-Map/css/styles.css">
  <style>
    html, body { height:100%; margin:0; }
    #edmap { width:100%; height:100vh; }
  </style>
</head>
<body>
  <div id="edmap"></div>

  <script src="Vendor/ED3D-Galaxy-Map/js/ed3dmap.js"></script>
  <script>
    // Expose sanitized params to JS (not required, but handy if you want to center later)
    const START = <?php echo json_encode(['x'=>$gx, 'y'=>$gy, 'z'=>$gz], JSON_UNESCAPED_SLASHES); ?>;
    const ZOOM  = <?php echo json_encode($zoom, JSON_UNESCAPED_SLASHES); ?>;

    Ed3d.init({
      container    : 'edmap',
      jsonPath     : './getMapPoints.json.php',
      withHudPanel : false
      // You can add ED3D options here later; for now we keep it minimal and safe.
      // If you want to center, do it in ED3D options after verifying support:
      // startPosition: [START.x, START.y, START.z]
    });
  </script>
</body>
</html>

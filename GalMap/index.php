<?php
declare(strict_types=1);

// Optional query params (kept safe if you want to use them later)
$gx = isset($_GET['x']) && is_numeric($_GET['x']) ? (float)$_GET['x'] : 0.0;
$gy = isset($_GET['y']) && is_numeric($_GET['y']) ? (float)$_GET['y'] : 0.0;
$gz = isset($_GET['z']) && is_numeric($_GET['z']) ? (float)$_GET['z'] : 0.0;
$zoom = isset($_GET['zoom']) && is_numeric($_GET['zoom']) ? (float)$_GET['zoom'] : 1.0;
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title>EDTB — Galaxy Map</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <!-- ED3D CSS (absolute path from repo root) -->
  <link rel="stylesheet" href="/Vendor/ED3D-Galaxy-Map/css/styles.css">
  <style>
    html, body { height:100%; margin:0; }
    #edmap { width:100%; height:100vh; }
  </style>
</head>
<body>
  <div id="edmap"></div>

  <!-- Required deps in this order: jQuery, Three.js, then ED3D -->
  <script src="/Vendor/ED3D-Galaxy-Map/vendor/jquery/jquery-2.1.4.min.js"></script>
  <script src="/Vendor/ED3D-Galaxy-Map/vendor/three/three.min.js"></script>
  <script src="/Vendor/ED3D-Galaxy-Map/js/ed3dmap.min.js"></script>

  <script>
    const START = <?php echo json_encode(['x'=>$gx, 'y'=>$gy, 'z'=>$gz], JSON_UNESCAPED_SLASHES); ?>;
    const ZOOM  = <?php echo json_encode($zoom, JSON_UNESCAPED_SLASHES); ?>;

    Ed3d.init({
      container      : 'edmap',
      jsonPath       : './getMapPoints.json.php',
      withHudPanel   : false,
      // Optional once you confirm it renders:
      // playerPos   : [START.x, START.y, START.z],
      // cameraPos   : [0, 45000, -45000]
    });
  </script>
</body>
</html>

<?php
declare(strict_types=1);

/**
 * JSON feed for ED3D Galaxy Map
 * Output format:
 * {
 *   "categories": { "Systems": {"name":"Systems","color":"#7cb5ec"} },
 *   "systems": [ {"name":"Sol","coords":[0,0,0],"cat":["Systems"]}, ... ],
 *   "resolved_center": {"name":"Sol","x":0,"y":0,"z":0}
 * }
 */
header('Content-Type: application/json; charset=utf-8');

$root = dirname(__DIR__);
require_once $root . '/source/config.inc.php';
if (file_exists($root . '/source/config_ini.inc.php')) require_once $root . '/source/config_ini.inc.php';
if (file_exists($root . '/source/MySQL.php')) require_once $root . '/source/MySQL.php';

if (!isset($mysqli) || !($mysqli instanceof mysqli)) {
    http_response_code(500);
    echo json_encode(['error' => 'db_not_connected'], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    exit;
}

function table_exists(mysqli $db, string $table): bool {
    $table = $db->real_escape_string($table);
    $res = $db->query("SHOW TABLES LIKE '{$table}'");
    return (bool)$res && $res->num_rows > 0;
}
function has_named_coords(mysqli $db, string $table): bool {
    $table = $db->real_escape_string($table);
    $sql = "SELECT 1 FROM `{$table}` 
            WHERE name IS NOT NULL AND name <> '' 
              AND x IS NOT NULL AND y IS NOT NULL AND z IS NOT NULL 
            LIMIT 1";
    $res = $db->query($sql);
    if ($res) { $ok = $res->num_rows > 0; $res->free(); return $ok; }
    return false;
}

$candidates = ['edtb_systems', 'systems', 'eddb_systems'];
$source = null;
foreach ($candidates as $cand) {
    if (table_exists($mysqli, $cand) && has_named_coords($mysqli, $cand)) { $source = $cand; break; }
}
if (!$source) {
    echo json_encode([
        'categories' => ['General' => ['Systems' => ['name' => 'Systems', 'color' => '7cb5ec']]],
        'systems' => [],
        'debug' => ['reason' => 'no_viable_source_table']

    ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    exit;
}

// Params
$centerSystem = trim((string)($_GET['center_system'] ?? ''));
$centerX = isset($_GET['centerX']) ? (float)$_GET['centerX'] : null;
$centerY = isset($_GET['centerY']) ? (float)$_GET['centerY'] : null;
$centerZ = isset($_GET['centerZ']) ? (float)$_GET['centerZ'] : null;
$maxDistance = isset($_GET['maxdistance']) ? (float)$_GET['maxdistance'] : 50.0;
$limit = (int)($_GET['limit'] ?? 15000);
if ($limit <= 0) $limit = 15000;
if ($limit > 50000) $limit = 50000;

$resolvedCenter = null;
// Resolve by name if coords missing
if (($centerX === null || $centerY === null || $centerZ === null) && $centerSystem !== '') {
    $esc = $mysqli->real_escape_string($centerSystem);
    $sql = "SELECT x, y, z FROM `{$source}` WHERE name = '{$esc}' LIMIT 1";
    if ($res = $mysqli->query($sql)) {
        if ($row = $res->fetch_assoc()) {
            $centerX = (float)$row['x'];
            $centerY = (float)$row['y'];
            $centerZ = (float)$row['z'];
            $resolvedCenter = ['name' => $centerSystem, 'x' => $centerX, 'y' => $centerY, 'z' => $centerZ];
        }
        $res->free();
    }
} elseif ($centerSystem !== '' && $centerX !== null && $centerY !== null && $centerZ !== null) {
    $resolvedCenter = ['name' => $centerSystem, 'x' => $centerX, 'y' => $centerY, 'z' => $centerZ];
}
require_once $root . '/GalMap/lib/cache.php';

// Build a stable cache key using the resolved inputs
$key = edtb_cache_key('galmap:points', [
    'src'   => $source,
    'cx'    => $centerX,
    'cy'    => $centerY,
    'cz'    => $centerZ,
    'cs'    => $centerSystem,
    'r'     => $maxDistance,
    'limit' => (int)$limit,
]);

if ($__cache = edtb_cache_instance()) {
    $cached = $__cache->get($key);
    if ($cached !== null && $cached !== false) {
        echo $cached;
        exit;
    }
}

// WHERE + optional distance predicate
$where = "WHERE s.name IS NOT NULL AND s.name <> '' AND s.x IS NOT NULL AND s.y IS NOT NULL AND s.z IS NOT NULL";
$distanceExpr = null;
if ($centerX !== null && $centerY !== null && $centerZ !== null && $maxDistance > 0) {
    $dx = (float)$centerX; $dy = (float)$centerY; $dz = (float)$centerZ; $r = (float)$maxDistance;
    $distanceExpr = "(POW(s.x - {$dx},2) + POW(s.y - {$dy},2) + POW(s.z - {$dz},2))";
    $where .= " AND {$distanceExpr} <= POW({$r},2)";
}

$order = $distanceExpr ? "ORDER BY {$distanceExpr} ASC" : "ORDER BY s.name ASC";
$limitSql = $limit > 0 ? "LIMIT {$limit}" : "";

$sql = "SELECT s.name, s.x, s.y, s.z
        FROM `{$source}` AS s
        {$where}
        {$order}
        {$limitSql}";

$out = [];
if ($res = $mysqli->query($sql)) {
    while ($row = $res->fetch_assoc()) {
        $name = (string)$row['name'];
        $x = (float)$row['x']; $y = (float)$row['y']; $z = (float)$row['z'];
        $out[] = ['name' => $name, 'coords' => [ $x, $y, $z ], 'cat' => ['Systems']];
    }
    $res->free();
} else {
    echo json_encode([
        'error' => 'query_failed',
        'message' => $mysqli->error,
        'sql' => $sql
    ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    exit;
}

$payload = [
    'categories' => ['General' => ['Systems' => ['name' => 'Systems', 'color' => '7cb5ec']]],
    'systems' => $out

];
if ($resolvedCenter !== null) $payload['resolved_center'] = $resolvedCenter;

$json = json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
if (isset($key) && ($__cache = edtb_cache_instance())) {
    $__cache->set($key, $json, EDTB_GALMAP_POINTS_TTL);
}
echo $json;

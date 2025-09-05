<?php
declare(strict_types=1);

/**
 * Self-contained JSON feed for Galaxy Map points.
 * - Picks first viable source table with named systems + non-null coords.
 * - center_system name is resolved to X/Y/Z if coordinates are not given.
 * - Optional spherical distance filter (no SQRT).
 * - Returns { systems: [...], resolved_center?: {...}, debug?: {...} }.
 */
header('Content-Type: application/json; charset=utf-8');

$root = dirname(__DIR__);

// --- Load config + DB bootstrap (use your existing includes) ---
require_once $root . '/source/config.inc.php';
if (file_exists($root . '/source/config_ini.inc.php')) {
    require_once $root . '/source/config_ini.inc.php';
}
if (file_exists($root . '/source/MySQL.php')) {
    require_once $root . '/source/MySQL.php';
}

// Expect a global $mysqli (from source/MySQL.php). Fail clearly if missing.
if (!isset($mysqli) || !($mysqli instanceof mysqli)) {
    http_response_code(500);
    echo json_encode([
        'error' => 'db_not_connected',
        'message' => 'MySQL connection ($mysqli) is not initialized. Ensure source/MySQL.php sets $mysqli.'
    ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    exit;
}

// --- Small helpers (local, no external deps) ---
function table_exists(mysqli $db, string $name): bool {
    $name = $db->real_escape_string($name);
    $res = $db->query("SHOW TABLES LIKE '{$name}'");
    if ($res) { $ok = $res->num_rows > 0; $res->free(); return $ok; }
    return false;
}
function table_has_named_coords(mysqli $db, string $table): bool {
    $table = $db->real_escape_string($table);
    $sql = "SELECT 1 FROM `{$table}` 
            WHERE name IS NOT NULL AND name <> '' 
              AND x IS NOT NULL AND y IS NOT NULL AND z IS NOT NULL 
            LIMIT 1";
    $res = $db->query($sql);
    if ($res) { $ok = $res->num_rows > 0; $res->free(); return $ok; }
    return false;
}

// --- Pick source table: edtb_systems → systems → eddb_systems (must have names + coords) ---
$sourceCandidates = ['edtb_systems', 'systems', 'eddb_systems'];
$sourceTable = null;
foreach ($sourceCandidates as $cand) {
    if (table_exists($mysqli, $cand) && table_has_named_coords($mysqli, $cand)) {
        $sourceTable = $cand;
        break;
    }
}
if (!$sourceTable) {
    echo json_encode([
        'systems' => [],
        'debug' => ['reason' => 'no_viable_source_table', 'candidates' => $sourceCandidates]
    ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    exit;
}

// --- Parse params ---
$limit        = isset($_GET['limit']) ? max(0, (int)$_GET['limit']) : 15000;
$maxDistance  = isset($_GET['maxdistance']) ? max(0, (float)$_GET['maxdistance']) : 0.0;
$centerX      = isset($_GET['centerX']) ? (float)$_GET['centerX'] : null;
$centerY      = isset($_GET['centerY']) ? (float)$_GET['centerY'] : null;
$centerZ      = isset($_GET['centerZ']) ? (float)$_GET['centerZ'] : null;
$centerSystem = $_GET['center_system'] ?? ($_GET['centerSystem'] ?? null);
$debugFlag    = isset($_GET['debug']);

// --- If only a name was provided, resolve it to coords from the chosen source table ---
$resolvedCenter = null;
if ($centerSystem && ($centerX === null || $centerY === null || $centerZ === null)) {
    if ($stmt = $mysqli->prepare("SELECT x, y, z FROM `{$sourceTable}` WHERE name = ? LIMIT 1")) {
        $stmt->bind_param('s', $centerSystem);
        if ($stmt->execute()) {
            $stmt->bind_result($cx, $cy, $cz);
            if ($stmt->fetch()) {
                $centerX = (float)$cx;
                $centerY = (float)$cy;
                $centerZ = (float)$cz;
                $resolvedCenter = ['name' => (string)$centerSystem, 'x' => $centerX, 'y' => $centerY, 'z' => $centerZ];
            }
        }
        $stmt->close();
    }
}

// --- Build WHERE + optional distance predicate ---
$where = "WHERE s.name IS NOT NULL AND s.name <> '' AND s.x IS NOT NULL AND s.y IS NOT NULL AND s.z IS NOT NULL";
$distanceExpr = null;
if ($centerX !== null && $centerY !== null && $centerZ !== null && $maxDistance > 0) {
    $dx = (float)$centerX; $dy = (float)$centerY; $dz = (float)$centerZ; $r = (float)$maxDistance;
    $distanceExpr = sprintf('(POW(s.x - %F,2) + POW(s.y - %F,2) + POW(s.z - %F,2))', $dx, $dy, $dz);
    $where .= sprintf(' AND %s <= POW(%F,2)', $distanceExpr, $r);
}

// --- Query (order by distance if filtering, else by name) ---
$order = $distanceExpr ? "ORDER BY {$distanceExpr} ASC" : "ORDER BY s.name ASC";
$limitSql = $limit > 0 ? "LIMIT {$limit}" : "";

$sql = "SELECT s.name, s.x, s.y, s.z
        FROM `{$sourceTable}` AS s
        {$where}
        {$order}
        {$limitSql}";

// --- Execute + emit ---
$out = [];
if ($res = $mysqli->query($sql)) {
    while ($row = $res->fetch_assoc()) {
        $out[] = [
            'name' => (string)$row['name'],
            'x'    => (float)$row['x'],
            'y'    => (float)$row['y'],
            'z'    => (float)$row['z'],
        ];
    }
    $res->free();
} else {
    http_response_code(500);
    echo json_encode([
        'error'   => 'query_failed',
        'message' => $mysqli->error,
        'sql'     => $debugFlag ? $sql : null
    ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    exit;
}

$payload = ['systems' => $out];
if ($resolvedCenter !== null) { $payload['resolved_center'] = $resolvedCenter; }
if ($debugFlag) {
    $dbRow = $mysqli->query('SELECT DATABASE() AS db')->fetch_assoc() ?: [];
    $payload['debug'] = ['db' => ($dbRow['db'] ?? null), 'source_table' => $sourceTable];
}
echo json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

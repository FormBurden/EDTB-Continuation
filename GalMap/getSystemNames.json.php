<?php
declare(strict_types=1);
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
    echo json_encode(['suggestions' => [], 'debug' => ['reason' => 'no_viable_source_table']], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    exit;
}

$q = trim((string)($_GET['q'] ?? ''));
$limit = (int)($_GET['limit'] ?? 15);
$exact = isset($_GET['exact']) && $_GET['exact'] !== '0' && $_GET['exact'] !== 'false';

if ($limit <= 0) $limit = 15;
if ($limit > 100) $limit = 100;

if ($q === '') {
    echo json_encode(['suggestions' => []], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    exit;
}
require_once $root . '/GalMap/lib/cache.php';

// Build a stable cache key from params & source table
$key = edtb_cache_key('galmap:suggest', [
    'src'   => $source,
    'q'     => $q,
    'exact' => (bool)$exact,
    'limit' => (int)$limit,
]);

if ($__cache = edtb_cache_instance()) {
    $cached = $__cache->get($key);
    if ($cached !== null && $cached !== false) {
        echo $cached;
        exit;
    }
}


$out = [];

if ($exact) {
    $sql = "SELECT name, x, y, z FROM `{$source}` WHERE name = ? LIMIT 1";
    if ($stmt = $mysqli->prepare($sql)) {
        $stmt->bind_param('s', $q);
        if ($stmt->execute()) {
            $stmt->bind_result($name, $x, $y, $z);
            while ($stmt->fetch()) {
                $out[] = ['name' => (string)$name, 'x' => (float)$x, 'y' => (float)$y, 'z' => (float)$z];
            }
        }
        $stmt->close();
    }
} else {
    $like1 = $q . '%';
    $like2 = '% ' . $q . '%';
    $sql = "SELECT name, x, y, z
            FROM `{$source}`
            WHERE name LIKE ? OR name LIKE ?
            ORDER BY name ASC
            LIMIT {$limit}";
    if ($stmt = $mysqli->prepare($sql)) {
        $stmt->bind_param('ss', $like1, $like2);
        if ($stmt->execute()) {
            $stmt->bind_result($name, $x, $y, $z);
            while ($stmt->fetch()) {
                $out[] = ['name' => (string)$name, 'x' => (float)$x, 'y' => (float)$y, 'z' => (float)$z];
            }
        }
        $stmt->close();
    }
}

$json = json_encode(['suggestions' => $out], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
if (isset($key) && ($__cache = edtb_cache_instance())) {
    $__cache->set($key, $json, EDTB_GALMAP_SUGGEST_TTL);
}
echo $json;


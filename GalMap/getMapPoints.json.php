<?php
declare(strict_types=1);
require_once __DIR__ . '/lib/GalMapParams.php';
use EDTB\GalMap\GalMapParams;

/**
 * Galaxy Map JSON feed for ED3D.
 * Emits either a flat array of systems or (if you prefer) wrap in {"systems":[...]}.
 * Minimal required fields: name, coords{x,y,z}
 */

header('Content-Type: application/json; charset=utf-8');
// Prevent any warnings/notices from corrupting JSON output:
ini_set('display_errors', '0');
error_reporting(E_ALL);

// Hard-fail catcher to return a valid JSON error if something goes sideways:
set_exception_handler(function ($e) {
    http_response_code(500);
    echo json_encode(['error' => 'server_error', 'message' => $e->getMessage()], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    exit;
});
set_error_handler(function($severity, $message, $file, $line) {
    // Convert to exception so our handler above returns JSON
    throw new ErrorException($message, 0, $severity, $file, $line);
});

$root = dirname(__DIR__);
require_once $root . '/source/config.inc.php';

// Try to acquire DB settings/connection in a portable way
$mysqli = null;
$server = [];
try {
    // Preferred: central MySQL helper (if your project uses it)
    $mysqlHelper = $root . '/source/MySQL.php';
    if (is_file($mysqlHelper)) {
        require_once $mysqlHelper;
        // Many EDTB forks instantiate $mysqli globally in MySQL.php; use it if available.
        if (isset($mysqli) && $mysqli instanceof mysqli) {
            // ok
        } else {
            // Fallback: read settings directly if MySQL.php exposes none
            $dataCfg = defined('EDTB_DATA') ? EDTB_DATA . '/server_config.inc.php' : $root . '/data/server_config.inc.php';
            if (is_file($dataCfg)) {
                $server = include $dataCfg;
            }
            // Try edtoolbox ini if present
            if (!$server) {
                $ini = $root . '/source/data/edtoolbox_v1.ini';
                if (is_file($ini)) {
                    $iniArr = parse_ini_file($ini, true, INI_SCANNER_TYPED) ?: [];
                    if (isset($iniArr['database'])) {
                        $server = [
                            'db_host' => $iniArr['database']['host'] ?? '127.0.0.1',
                            'db_name' => $iniArr['database']['name'] ?? 'edtb',
                            'db_user' => $iniArr['database']['user'] ?? 'edtb',
                            'db_pass' => $iniArr['database']['pass'] ?? ($iniArr['database']['password'] ?? ''),
                            'db_port' => (int)($iniArr['database']['port'] ?? 3306),
                        ];
                    }
                }
            }
            if (!$server) {
                // Last resort: environment
                $server = [
                    'db_host' => $_ENV['DB_HOST'] ?? '127.0.0.1',
                    'db_name' => $_ENV['DB_NAME'] ?? 'edtb',
                    'db_user' => $_ENV['DB_USER'] ?? 'edtb',
                    'db_pass' => $_ENV['DB_PASS'] ?? '',
                    'db_port' => (int)($_ENV['DB_PORT'] ?? 3306),
                ];
            }
            $mysqli = new mysqli(
                $server['db_host'] ?? '127.0.0.1',
                $server['db_user'] ?? 'edtb',
                $server['db_pass'] ?? '',
                $server['db_name'] ?? 'edtb',
                (int)($server['db_port'] ?? 3306)
            );
            if ($mysqli->connect_errno) {
                throw new RuntimeException('DB connect failed: ' . $mysqli->connect_error);
            }
        }
    } else {
        // No helper present: go straight to config files/env
        $dataCfg = defined('EDTB_DATA') ? EDTB_DATA . '/server_config.inc.php' : $root . '/data/server_config.inc.php';
        if (is_file($dataCfg)) {
            $server = include $dataCfg;
        }
        if (!$server) {
            $ini = $root . '/source/data/edtoolbox_v1.ini';
            if (is_file($ini)) {
                $iniArr = parse_ini_file($ini, true, INI_SCANNER_TYPED) ?: [];
                if (isset($iniArr['database'])) {
                    $server = [
                        'db_host' => $iniArr['database']['host'] ?? '127.0.0.1',
                        'db_name' => $iniArr['database']['name'] ?? 'edtb',
                        'db_user' => $iniArr['database']['user'] ?? 'edtb',
                        'db_pass' => $iniArr['database']['pass'] ?? ($iniArr['database']['password'] ?? ''),
                        'db_port' => (int)($iniArr['database']['port'] ?? 3306),
                    ];
                }
            }
        }
        if (!$server) {
            $server = [
                'db_host' => $_ENV['DB_HOST'] ?? '127.0.0.1',
                'db_name' => $_ENV['DB_NAME'] ?? 'edtb',
                'db_user' => $_ENV['DB_USER'] ?? 'edtb',
                'db_pass' => $_ENV['DB_PASS'] ?? '',
                'db_port' => (int)($_ENV['DB_PORT'] ?? 3306),
            ];
        }
        $mysqli = new mysqli(
            $server['db_host'] ?? '127.0.0.1',
            $server['db_user'] ?? 'edtb',
            $server['db_pass'] ?? '',
            $server['db_name'] ?? 'edtb',
            (int)($server['db_port'] ?? 3306)
        );
        if ($mysqli->connect_errno) {
            throw new RuntimeException('DB connect failed: ' . $mysqli->connect_error);
        }
    }
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['error' => 'db_init_failed', 'message' => $e->getMessage()]);
    exit;
}

// Helpers
function table_exists(mysqli $db, string $table): bool {
    $res = $db->query("SHOW TABLES LIKE '" . $db->real_escape_string($table) . "'");
    return $res && $res->num_rows > 0;
}
function pick_first_existing_table(mysqli $db, array $candidates): ?string {
    foreach ($candidates as $t) {
        if (table_exists($db, $t)) return $t;
    }
    return null;
}
$params = GalMapParams::fromRequest($_GET);


// Inputs
$limit       = max(1, min(50000, (int)($_GET['limit'] ?? 15000)));
$visitedOnly = isset($_GET['visited_only']) && $_GET['visited_only'] === '1';
$bmOnly      = isset($_GET['bookmarked_only']) && $_GET['bookmarked_only'] === '1';

// Pick a source table for coordinates
$sourceTable = pick_first_existing_table($mysqli, [
    'edtb_systems',
    'systems',
    'eddb_systems'
]);
if (!$sourceTable) {
    echo json_encode([], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    exit;
}

// Optional joins for visited/bookmarks if present
$visitedTable   = table_exists($mysqli, 'user_visited') ? 'user_visited' : (table_exists($mysqli, 'user_visited_systems') ? 'user_visited_systems' : null);
$bookmarksTable = table_exists($mysqli, 'user_bookmarks') ? 'user_bookmarks' : (table_exists($mysqli, 'edtb_bookmarks') ? 'edtb_bookmarks' : null);

// Build WHERE/JOINS
$joins = [];
$where = ["s.x IS NOT NULL", "s.y IS NOT NULL", "s.z IS NOT NULL"];
$order = "s.name ASC";
// Optional spherical distance filter when center + maxDistance are provided
if ($params->maxDistance !== null
    && $params->centerX !== null
    && $params->centerY !== null
    && $params->centerZ !== null) {

    $dx = (float)$params->centerX;
    $dy = (float)$params->centerY;
    $dz = (float)$params->centerZ;
    $r  = (float)$params->maxDistance;

    // Use squared distance to avoid SQRT in MySQL for performance
    $where[] = sprintf(
        '(POW(s.x - %F, 2) + POW(s.y - %F, 2) + POW(s.z - %F, 2)) <= POW(%F, 2)',
        $dx, $dy, $dz, $r
    );
}


if ($visitedOnly && $visitedTable) {
    $joins[] = "INNER JOIN {$visitedTable} uv ON (uv.system_name = s.name)";
    $order = "uv.last_visit DESC";
}
if ($bmOnly && $bookmarksTable) {
    $joins[] = "INNER JOIN {$bookmarksTable} bm ON (bm.system_name = s.name)";
    if (!$visitedOnly) {
        $order = "bm.added_at DESC";
    }
}

$sql = "SELECT s.name, s.x, s.y, s.z
        FROM {$sourceTable} s
        " . implode("\n        ", $joins) . "
        WHERE " . implode(' AND ', $where) . "
        ORDER BY {$order}
        LIMIT {$limit}";

$res = $mysqli->query($sql);
if (!$res) {
    throw new RuntimeException('Query failed: ' . $mysqli->error);
}

$out = [];
while ($row = $res->fetch_assoc()) {
    $name = (string)($row['name'] ?? '');
    if ($name === '') {
        continue;
    }
    $x = is_numeric($row['x'] ?? null) ? (float)$row['x'] : null;
    $y = is_numeric($row['y'] ?? null) ? (float)$row['y'] : null;
    $z = is_numeric($row['z'] ?? null) ? (float)$row['z'] : null;
    if ($x === null || $y === null || $z === null) {
        continue;
    }
    $out[] = [
        'name'   => $name,
        'coords' => ['x' => $x, 'y' => $y, 'z' => $z],
        // Add optional keys here later if desired:
        // 'infos' => 'Visited/bookmarked/etc.',
        // 'url'   => '/System/?name=' . rawurlencode($name),
    ];
}
$res->free();

echo json_encode(['systems' => $out], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

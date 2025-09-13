<?php
declare(strict_types=1);
header('Content-Type: application/json; charset=utf-8');

$root = dirname(__DIR__);

// Core includes
require_once $root . '/source/config.inc.php';
require_once $root . '/GalMap/lib/cache.php';

// -----------------------------------------------------------------------------
// Ensure we have a mysqli handle ($mysqli)
// -----------------------------------------------------------------------------
if (!isset($mysqli) || !($mysqli instanceof mysqli)) {
    if (isset($GLOBALS['mysqli']) && $GLOBALS['mysqli'] instanceof mysqli) {
        $mysqli = $GLOBALS['mysqli'];
    } elseif (isset($GLOBALS['___mysqli_ston']) && $GLOBALS['___mysqli_ston'] instanceof mysqli) {
        $mysqli = $GLOBALS['___mysqli_ston'];
    } else {
        $mysqlHelper = $root . '/source/MySQL.php';
        if (is_file($mysqlHelper)) {
            require_once $mysqlHelper;
            if (class_exists('MySQL')) {
                if (method_exists('MySQL', 'getInstance')) {
                    $inst = @MySQL::getInstance();
                    if ($inst) {
                        if (method_exists($inst, 'getConnection')) {
                            $mysqli = $inst->getConnection();
                        } elseif (method_exists($inst, 'getLink')) {
                            $mysqli = $inst->getLink();
                        } elseif (property_exists($inst, 'mysqli')) {
                            $mysqli = $inst->mysqli;
                        }
                    }
                }
                if (!isset($mysqli) || !($mysqli instanceof mysqli)) {
                    if (method_exists('MySQL', 'connection')) {
                        $mysqli = @MySQL::connection();
                    }
                }
            }
        }
    }
}

if (!isset($mysqli) || !($mysqli instanceof mysqli)) {
    http_response_code(500);
    echo json_encode(['error' => 'db_unavailable'], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    exit;
}

// -----------------------------------------------------------------------------
// Inputs
// -----------------------------------------------------------------------------
$sourceParam = $_GET['source'] ?? '';
$source      = preg_match('/^[A-Za-z0-9_]+$/', $sourceParam) ? $sourceParam : 'edtb_systems';

$centerSystem = trim((string)($_GET['center_system'] ?? ''));
$centerX      = isset($_GET['centerX']) ? (float)$_GET['centerX'] : null;
$centerY      = isset($_GET['centerY']) ? (float)$_GET['centerY'] : null;
$centerZ      = isset($_GET['centerZ']) ? (float)$_GET['centerZ'] : null;

$maxDistance  = isset($_GET['maxdistance']) ? max(0.0, (float)$_GET['maxdistance']) : 50.0;
$limit        = isset($_GET['limit']) ? (int)$_GET['limit'] : 15000;
if ($limit < 1)      $limit = 1;
if ($limit > 15000)  $limit = 15000;

$visitedOnly    = isset($_GET['visited_only'])    && (string)$_GET['visited_only']    === '1';
$bookmarkedOnly = isset($_GET['bookmarked_only']) && (string)$_GET['bookmarked_only'] === '1';

$colorBy = strtolower((string)($_GET['color_by'] ?? 'none'));
$allowedColorBy = ['none','allegiance','government','economy','security'];
if (!in_array($colorBy, $allowedColorBy, true)) {
    $colorBy = 'none';
}

// Collation to use when comparing system names across tables (avoids mix errors)
$COLLATE = 'utf8mb4_unicode_ci';

// -----------------------------------------------------------------------------
// Cache: build key & short-circuit
// -----------------------------------------------------------------------------
$keyParts = [
    'src'        => $source,
    'cs'         => $centerSystem,
    'cx'         => $centerX,
    'cy'         => $centerY,
    'cz'         => $centerZ,
    'r'          => $maxDistance,
    'limit'      => $limit,
    'visited'    => (int)$visitedOnly,
    'bookmarked' => (int)$bookmarkedOnly,
    'color_by'   => $colorBy,
];
$key = edtb_cache_key('galmap:points', $keyParts);

$__cache = edtb_cache_instance();
if ($__cache) {
    $cached = $__cache->get($key);
    if ($cached !== null && $cached !== false) {
        echo $cached;
        exit;
    }
}

// -----------------------------------------------------------------------------
// Resolve center (if needed) by name
// -----------------------------------------------------------------------------
$resolvedCenter = null;

if ($centerSystem !== '' && ($centerX === null || $centerY === null || $centerZ === null)) {
    $nameEsc = $mysqli->real_escape_string($centerSystem);
    // Collate both sides to avoid collation mismatch when resolving by name
    $sqlCenter = "SELECT s.name, s.x, s.y, s.z
                  FROM `{$source}` AS s
                  WHERE s.name COLLATE {$COLLATE} = '{$nameEsc}' COLLATE {$COLLATE}
                  LIMIT 1";
    if ($resC = $mysqli->query($sqlCenter)) {
        if ($rowC = $resC->fetch_assoc()) {
            $centerSystem = (string)$rowC['name'];
            $centerX = (float)$rowC['x'];
            $centerY = (float)$rowC['y'];
            $centerZ = (float)$rowC['z'];
        }
        $resC->free();
    }
}

if ($centerX !== null && $centerY !== null && $centerZ !== null) {
    $resolvedCenter = [
        'name' => $centerSystem !== '' ? $centerSystem : null,
        'x'    => (float)$centerX,
        'y'    => (float)$centerY,
        'z'    => (float)$centerZ,
    ];
}

// -----------------------------------------------------------------------------
// Table column discovery (to support varied schemas)
// -----------------------------------------------------------------------------
$cols = [];
if ($rsCols = $mysqli->query("SHOW COLUMNS FROM `{$source}`")) {
    while ($r = $rsCols->fetch_assoc()) {
        $n = strtolower($r['Field']);
        $cols[$n] = true;
    }
    $rsCols->free();
}

$present = function(array $candidates) use ($cols): array {
    $out = [];
    foreach ($candidates as $c) {
        if (isset($cols[strtolower($c)])) {
            $out[] = $c;
        }
    }
    return $out;
};

$coalesceExpr = function(array $names): string {
    if (empty($names)) return 'NULL';
    $parts = [];
    foreach ($names as $n) {
        $parts[] = "NULLIF(TRIM(s.`{$n}`), '')";
    }
    return 'COALESCE(' . implode(', ', $parts) . ')';
};

// Candidate lists for each color mode
$cand = [
    'allegiance' => ['allegiance','faction_allegiance','controlling_allegiance','allegiance_name'],
    'government' => ['government','faction_government','controlling_government','government_name'],
    'economy'    => ['economy','primary_economy','economy_primary','economy_name'],
    'security'   => ['security','security_level','security_name'],
];

// Build grp expression based on existing columns
$grpExpr = 'NULL';
if ($colorBy !== 'none') {
    $avail = $present($cand[$colorBy] ?? []);
    $grpExpr = $coalesceExpr($avail);
}

// -----------------------------------------------------------------------------
// Build query
// -----------------------------------------------------------------------------
$where    = "WHERE s.name IS NOT NULL";
$order    = "ORDER BY s.name ASC";
$limitSql = "LIMIT {$limit}";

// Distance expression (only used if center present)
$distanceExpr = null;
if ($centerX !== null && $centerY !== null && $centerZ !== null) {
    $cx = (float)$centerX; $cy = (float)$centerY; $cz = (float)$centerZ;
    $dx = "(s.x - {$cx})"; $dy = "(s.y - {$cy})"; $dz = "(s.z - {$cz})";
    // Use squared distance for filtering; sqrt only when ordering/returning
    $dist2 = "({$dx}*{$dx} + {$dy}*{$dy} + {$dz}*{$dz})";
    $where .= " AND {$dist2} <= " . ($maxDistance * $maxDistance);
    $distanceExpr = "SQRT({$dist2})";
    $order = "ORDER BY {$distanceExpr} ASC";
}

// Apply visited/bookmarked filters (collate both sides)
if (!empty($visitedOnly)) {
    $where .= " AND EXISTS (
        SELECT 1 FROM `user_visited_systems` uvs
        WHERE uvs.system_name COLLATE {$COLLATE} = s.name COLLATE {$COLLATE}
    )";
}
if (!empty($bookmarkedOnly)) {
    $where .= " AND EXISTS (
        SELECT 1 FROM `user_bookmarks` ub
        WHERE ub.system_name COLLATE {$COLLATE} = s.name COLLATE {$COLLATE}
    )";
}

// Select systems, plus visited/bookmarked flags using EXISTS with explicit collation
$selectVisited = "EXISTS (SELECT 1 FROM `user_visited_systems` uvs WHERE uvs.system_name COLLATE {$COLLATE} = s.name COLLATE {$COLLATE}) AS visited";
$selectBookmk  = "EXISTS (SELECT 1 FROM `user_bookmarks` ub WHERE ub.system_name COLLATE {$COLLATE} = s.name COLLATE {$COLLATE}) AS bookmarked";
$selectDist    = $distanceExpr ? "{$distanceExpr} AS dist" : "NULL AS dist";
$selectGrp     = ($colorBy !== 'none') ? "{$grpExpr} AS grp" : "NULL AS grp";

$sql = "SELECT
        s.name, s.x, s.y, s.z,
        {$selectVisited},
        {$selectBookmk},
        {$selectDist},
        {$selectGrp}
        FROM `{$source}` AS s
        {$where}
        {$order}
        {$limitSql}";

// -----------------------------------------------------------------------------
// Execute & build payload
// -----------------------------------------------------------------------------
$out = [];
$catIndex = []; // category name => 1
if ($res = $mysqli->query($sql)) {
    while ($row = $res->fetch_assoc()) {
        $name = (string)$row['name'];
        $x = (float)$row['x']; $y = (float)$row['y']; $z = (float)$row['z'];
        $visited = isset($row['visited']) ? (int)$row['visited'] : 0;
        $bookmarked = isset($row['bookmarked']) ? (int)$row['bookmarked'] : 0;
        $dist = (isset($row['dist']) && $row['dist'] !== null) ? (float)$row['dist'] : null;

        // Category (color grouping)
        $catName = 'Systems';
        if ($colorBy !== 'none') {
            $g = isset($row['grp']) && $row['grp'] !== null && $row['grp'] !== '' ? (string)$row['grp'] : 'Unknown';
            $catName = $g;
            $catIndex[$catName] = 1;
        } else {
            $catIndex['Systems'] = 1;
        }

        $item = [
            'name'       => $name,
            'coords'     => [ 'x' => (float)$x, 'y' => (float)$y, 'z' => (float)$z ],
            'cat'        => [ $catName ],
            'visited'    => $visited,
            'bookmarked' => $bookmarked,
        ];
        if ($dist !== null) {
            $item['dist'] = $dist;
        }
        $out[] = $item;
    }
    $res->free();
}

// Build categories & colors (original-like palette)
function mapColor(string $mode, string $label): string {
    static $pal = [
        'allegiance' => [
            'Alliance' => '#2ecc71',
            'Empire' => '#e74c3c',
            'Federation' => '#3498db',
            'Independent' => '#95a5a6',
            'Pilots Federation' => '#f1c40f',
            'Thargoid' => '#8e44ad',
            'Unknown' => '#7f8c8d',
        ],
        'government' => [
            'Anarchy' => '#7f8c8d',
            'Communism' => '#d35400',
            'Confederacy' => '#9b59b6',
            'Corporate' => '#2980b9',
            'Cooperative' => '#27ae60',
            'Democracy' => '#16a085',
            'Dictatorship' => '#c0392b',
            'Feudal' => '#8e44ad',
            'Patronage' => '#f1c40f',
            'Prison Colony' => '#34495e',
            'Theocracy' => '#2c3e50',
            'Engineer' => '#e67e22',
            'Unknown' => '#7f8c8d',
        ],
        'economy' => [
            'Agriculture' => '#2ecc71',
            'Extraction' => '#7f8c8d',
            'High Tech' => '#2980b9',
            'Industrial' => '#e67e22',
            'Military' => '#c0392b',
            'Refinery' => '#8e44ad',
            'Service' => '#16a085',
            'Terraforming' => '#27ae60',
            'Tourism' => '#f1c40f',
            'Colony' => '#9b59b6',
            'Unknown' => '#7f8c8d',
        ],
        'security' => [
            'High' => '#27ae60',
            'Medium' => '#f39c12',
            'Low' => '#e67e22',
            'Anarchy' => '#c0392b',
            'Lawless' => '#7f8c8d',
            'Unknown' => '#7f8c8d',
        ],
        'none' => [
            'Systems' => '#7cb5ec',
        ],
    ];
    $m = $pal[$mode] ?? $pal['none'];
    return $m[$label] ?? ($m['Unknown'] ?? '#7f8c8d');
}

$categories = [];
if ($colorBy === 'none') {
    $categories = ['Systems' => ['name' => 'Systems', 'color' => '#7cb5ec']];
} else {
    foreach (array_keys($catIndex) as $label) {
        $categories[$label] = [
            'name'  => $label,
            'color' => mapColor($colorBy, $label),
        ];
    }
}

// Final payload
$payload = [
    'resolved_center' => $resolvedCenter,
    'color_by'        => $colorBy,
    'categories'      => $categories,
    'systems'         => $out,
];

// -----------------------------------------------------------------------------
// Cache & emit
// -----------------------------------------------------------------------------
$json = json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
if ($__cache) {
    $__cache->set($key, $json, EDTB_GALMAP_POINTS_TTL);
}
echo $json;

<?php
/**
 * Backfill system information (allegiance, government, population, economy, faction)
 * into MariaDB from EDSM.
 *
 * Modes:
 *   # Specific systems
 *   php scripts/ingest/backfill_system_info.php "Sol" "Lave" "Shinrarta Dezhra"
 *
 *   # Recent visited (from user_system_map)
 *   php scripts/ingest/backfill_system_info.php --recent=100
 *
 *   # DB-radius mode (uses coords already in your 'systems' table)
 *   php scripts/ingest/backfill_system_info.php --center="Sol" --within=50 --limit=500
 *
 *   # EDSM-radius mode (queries EDSM for names within radius; also writes coords into DB)
 *   php scripts/ingest/backfill_system_info.php --center="Sol" --within=50 --limit=500 --source=edsm
 */

$ROOT = realpath(__DIR__ . '/../..');
require_once $ROOT . '/source/functions.php';
require_once $ROOT . '/source/MySQL.php'; // provides $mysqli

// ---------------- helpers ----------------
function http_get($url) {
    $ch = curl_init($url);
    curl_setopt_array($ch, array(
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 20,
        CURLOPT_CONNECTTIMEOUT => 8,
        CURLOPT_USERAGENT => "EDTB-Continuation Backfill/1.3",
    ));
    $resp = curl_exec($ch);
    $err  = curl_error($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    if ($resp === false || $code !== 200) {
        echo "[WARN] GET failed ($code): $url  $err\n";
        return null;
    }
    return $resp;
}

function edsm_fetch($systemName) {
    $url = "https://www.edsm.net/api-v1/system?showInformation=1&showId=1&showCoordinates=1&systemName=" . rawurlencode($systemName);
    $resp = http_get($url);
    if (!$resp) return null;
    $j = json_decode($resp, true);
    if (!is_array($j)) return null;

    $info = isset($j['information']) && is_array($j['information']) ? $j['information'] : array();
    return array(
        'allegiance' => ($v = trim((string)(@$info['allegiance']))) !== '' ? $v : null,
        'government' => ($v = trim((string)(@$info['government']))) !== '' ? $v : null,
        'economy'    => ($v = trim((string)(@$info['economy'])))    !== '' ? $v : null,
        'population' => (isset($info['population']) && $info['population'] !== '') ? (int)$info['population'] : null,
        'faction'    => ($v = trim((string)(@$info['faction'])))    !== '' ? $v : null,
        'coords'     => !empty($j['coords']) ? array(
            'x' => isset($j['coords']['x']) ? (float)$j['coords']['x'] : null,
            'y' => isset($j['coords']['y']) ? (float)$j['coords']['y'] : null,
            'z' => isset($j['coords']['z']) ? (float)$j['coords']['z'] : null,
        ) : null,
    );
}

function ensure_columns($mysqli, $table) {
    $mysqli->query("ALTER TABLE `{$table}`
        ADD COLUMN IF NOT EXISTS `allegiance`       varchar(64) NULL,
        ADD COLUMN IF NOT EXISTS `government`       varchar(64) NULL,
        ADD COLUMN IF NOT EXISTS `economy`          varchar(64) NULL,
        ADD COLUMN IF NOT EXISTS `population`       BIGINT NULL,
        ADD COLUMN IF NOT EXISTS `ruling_faction`   varchar(128) NULL
    ") or write_log($mysqli->error, __FILE__, __LINE__);

    // coords (some installs miss these)
    if ($table === 'systems') {
        $mysqli->query("ALTER TABLE `systems`
            ADD COLUMN IF NOT EXISTS `x` DOUBLE NULL,
            ADD COLUMN IF NOT EXISTS `y` DOUBLE NULL,
            ADD COLUMN IF NOT EXISTS `z` DOUBLE NULL
        ") or write_log($mysqli->error, __FILE__, __LINE__);
    }
}

function upsert_systems($mysqli, $table, $name, $d) {
    $a  = isset($d['allegiance']) ? $d['allegiance'] : null;
    $g  = isset($d['government']) ? $d['government'] : null;
    $e  = isset($d['economy'])    ? $d['economy']    : null;
    $p  = isset($d['population']) ? $d['population'] : null;
    $rf = isset($d['faction'])    ? $d['faction']    : null;

    $nameEsc = $mysqli->real_escape_string($name);
    $aEsc    = $a !== null ? ("'".$mysqli->real_escape_string($a)."'") : "NULL";
    $gEsc    = $g !== null ? ("'".$mysqli->real_escape_string($g)."'") : "NULL";
    $eEsc    = $e !== null ? ("'".$mysqli->real_escape_string($e)."'") : "NULL";
    $pVal    = $p !== null ? (string)(int)$p : "NULL";
    $rfEsc   = $rf !== null ? ("'".$mysqli->real_escape_string($rf)."'") : "NULL";

    $mysqli->query("INSERT IGNORE INTO `{$table}` (`name`) VALUES ('{$nameEsc}')");
    $sql = "UPDATE `{$table}`
            SET allegiance={$aEsc},
                government={$gEsc},
                economy={$eEsc},
                population={$pVal},
                ruling_faction={$rfEsc}
            WHERE name='{$nameEsc}'
            LIMIT 1";
    $mysqli->query($sql) or write_log($mysqli->error, __FILE__, __LINE__);
}

function upsert_coords($mysqli, $name, $x, $y, $z) {
    if ($x === null || $y === null || $z === null) return;
    $nameEsc = $mysqli->real_escape_string($name);
    $mysqli->query("INSERT IGNORE INTO `systems` (`name`) VALUES ('{$nameEsc}')");
    $sql = "UPDATE `systems`
            SET x={$x}, y={$y}, z={$z}
            WHERE name='{$nameEsc}'
            LIMIT 1";
    $mysqli->query($sql) or write_log($mysqli->error, __FILE__, __LINE__);
}

function get_center_coords_db($mysqli, $name) {
    $nameEsc = $mysqli->real_escape_string($name);
    $res = $mysqli->query("SELECT x,y,z FROM systems WHERE name='{$nameEsc}' LIMIT 1");
    if (!$res) return null;
    $row = $res->fetch_assoc();
    $res->close();
    if (!$row || $row['x'] === null || $row['y'] === null || $row['z'] === null) return null;
    return array((float)$row['x'], (float)$row['y'], (float)$row['z']);
}

function get_center_coords_edsm($name) {
    $url  = "https://www.edsm.net/api-v1/system?showCoordinates=1&systemName=" . rawurlencode($name);
    $resp = http_get($url);
    if (!$resp) return null;
    $j = json_decode($resp, true);
    if (!is_array($j) || empty($j['coords'])) return null;
    return array((float)$j['coords']['x'], (float)$j['coords']['y'], (float)$j['coords']['z']);
}

function names_within_radius_db($mysqli, $cx, $cy, $cz, $radiusLy, $limit) {
    $r2 = $radiusLy * $radiusLy;
    $sql = "SELECT name
            FROM systems
            WHERE x IS NOT NULL AND y IS NOT NULL AND z IS NOT NULL
              AND ( POW(x-{$cx},2) + POW(y-{$cy},2) + POW(z-{$cz},2) ) <= {$r2}
            ORDER BY ( POW(x-{$cx},2) + POW(y-{$cy},2) + POW(z-{$cz},2) ) ASC
            LIMIT {$limit}";
    $names = array();
    $res = $mysqli->query($sql);
    if ($res) {
        while ($r = $res->fetch_assoc()) { $names[] = $r['name']; }
        $res->close();
    }
    return $names;
}

function names_within_radius_edsm($mysqli, $cx, $cy, $cz, $radiusLy, $limit, $centerName = null) {
    // Some EDSM deployments return the literal number 0 instead of [] when there are no results.
    // We'll try three strategies in order:
    //   1) sphere by systemName
    //   2) sphere by coordinates
    //   3) cube by coordinates (then filter to a sphere)
    $r  = (float)$radiusLy;
    $cx = (float)$cx; $cy = (float)$cy; $cz = (float)$cz;

    // Helper to parse responses that might be "0", [], or a proper array
    $parseList = function ($json) {
        if ($json === null || $json === '' ) return array();
        $arr = json_decode($json, true);
        if ($arr === 0) return array();               // literal 0
        if ($arr === null) return array();            // bad JSON
        if (is_array($arr)) return $arr;              // ok (might be [])
        return array();                                // anything else
    };

    // 1) Try name-based sphere
    if ($centerName !== null && $centerName !== '') {
        $url = "https://www.edsm.net/api-v1/sphere-systems?showCoordinates=1&showId=1&minRadius=0&systemName="
             . rawurlencode($centerName) . "&radius={$r}";
        $resp = http_get($url);
        $arr  = $parseList($resp);
        if (!empty($arr)) {
            $names = array();
            foreach ($arr as $sys) {
                $n = isset($sys['name']) ? (string)$sys['name'] : '';
                if ($n === '') continue;
                $names[] = $n;
                if (!empty($sys['coords'])) {
                    $x = isset($sys['coords']['x']) ? (float)$sys['coords']['x'] : null;
                    $y = isset($sys['coords']['y']) ? (float)$sys['coords']['y'] : null;
                    $z = isset($sys['coords']['z']) ? (float)$sys['coords']['z'] : null;
                    upsert_coords($mysqli, $n, $x, $y, $z);
                }
            }
            if ($limit > 0 && count($names) > $limit) {
                $names = array_slice($names, 0, $limit);
            }
            return $names;
        }
    }

    // 2) Try sphere by explicit coordinates
    $url = "https://www.edsm.net/api-v1/sphere-systems?showCoordinates=1&showId=1&minRadius=0"
         . "&x={$cx}&y={$cy}&z={$cz}&radius={$r}";
    $resp = http_get($url);
    $arr  = $parseList($resp);
    if (!empty($arr)) {
        $names = array();
        foreach ($arr as $sys) {
            $n = isset($sys['name']) ? (string)$sys['name'] : '';
            if ($n === '') continue;
            $names[] = $n;
            if (!empty($sys['coords'])) {
                $x = isset($sys['coords']['x']) ? (float)$sys['coords']['x'] : null;
                $y = isset($sys['coords']['y']) ? (float)$sys['coords']['y'] : null;
                $z = isset($sys['coords']['z']) ? (float)$sys['coords']['z'] : null;
                upsert_coords($mysqli, $n, $x, $y, $z);
            }
        }
        if ($limit > 0 && count($names) > $limit) {
            $names = array_slice($names, 0, $limit);
        }
        return $names;
    }

    // 3) Fallback: cube by coordinates, then filter to a sphere on our side
    // Use size = 2r so the cube fully encloses the desired sphere.
    $size = $r * 2.0;
    $url  = "https://www.edsm.net/api-v1/cube-systems?showCoordinates=1&showId=1"
          . "&x={$cx}&y={$cy}&z={$cz}&size={$size}";
    $resp = http_get($url);
    $arr  = $parseList($resp);
    if (empty($arr)) return array();

    $names = array();
    $r2 = $r * $r;
    foreach ($arr as $sys) {
        $n = isset($sys['name']) ? (string)$sys['name'] : '';
        if ($n === '') continue;

        $x = isset($sys['coords']['x']) ? (float)$sys['coords']['x'] : null;
        $y = isset($sys['coords']['y']) ? (float)$sys['coords']['y'] : null;
        $z = isset($sys['coords']['z']) ? (float)$sys['coords']['z'] : null;

        // Must have coords to distance-filter; skip otherwise
        if ($x === null || $y === null || $z === null) continue;

        $d2 = ($x - $cx)*($x - $cx) + ($y - $cy)*($y - $cy) + ($z - $cz)*($z - $cz);
        if ($d2 <= $r2) {
            $names[] = $n;
            upsert_coords($mysqli, $n, $x, $y, $z); // cache locally
        }
    }

    if ($limit > 0 && count($names) > $limit) {
        $names = array_slice($names, 0, $limit);
    }
    return $names;
}



// ---------------- args ----------------
$argvCopy = $argv;
array_shift($argvCopy); // drop script name
$recent = 0;
$within = 0.0;
$center = null;
$limit  = 2000;
$source = 'db'; // db | edsm
$names  = array();

foreach ($argvCopy as $arg) {
    if (preg_match('/^--recent=(\d{1,6})$/', $arg, $m)) {
        $recent = (int)$m[1];
    } elseif (preg_match('/^--within=([0-9]+(?:\.[0-9]+)?)$/', $arg, $m)) {
        $within = (float)$m[1];
    } elseif (preg_match('/^--center=(.*)$/', $arg, $m)) {
        $center = trim($m[1], "\"'");
    } elseif (preg_match('/^--limit=(\d{1,7})$/', $arg, $m)) {
        $limit = (int)$m[1];
    } elseif (preg_match('/^--source=(db|edsm)$/i', $arg, $m)) {
        $source = strtolower($m[1]);
    } else {
        // positional: treat as system name
        $names[] = $arg;
    }
}

// pull from recent history
if ($recent > 0) {
    $res = $mysqli->query("SELECT DISTINCT system_name AS name
                           FROM user_system_map
                           WHERE system_name IS NOT NULL AND system_name <> ''
                           ORDER BY id DESC
                           LIMIT {$recent}");
    if ($res) {
        while ($row = $res->fetch_object()) { $names[] = $row->name; }
        $res->close();
    }
}

// radius modes
if ($within > 0.0) {
    if (!$center) {
        fwrite(STDERR, "[ERR] --within requires --center=\"System Name\".\n");
        exit(2);
    }

    ensure_columns($mysqli, 'systems');

    // center coords: try DB then EDSM
    // center coords: prefer EDSM if DB has 0/0/0 or is missing
    $coords = get_center_coords_db($mysqli, $center);
    if (
        !$coords ||
        ((float)$coords[0] == 0.0 && (float)$coords[1] == 0.0 && (float)$coords[2] == 0.0)
    ) {
        $coords = get_center_coords_edsm($center);
        if ($coords) {
            upsert_coords($mysqli, $center, $coords[0], $coords[1], $coords[2]); // cache for next time
        }
    }
    if (!$coords) {
        fwrite(STDERR, "[ERR] Could not resolve center coords for '{$center}'.\n");
        exit(2);
    }
    $cx = $coords[0]; $cy = $coords[1]; $cz = $coords[2];


    if ($source === 'edsm') {
        $radNames = names_within_radius_edsm($mysqli, $cx, $cy, $cz, $within, $limit, $center);
        echo "[INFO] (EDSM) Selected ".count($radNames)." systems within {$within} ly of {$center}.\n";
    } else {
        $radNames = names_within_radius_db($mysqli, $cx, $cy, $cz, $within, $limit);
        echo "[INFO] (DB) Selected ".count($radNames)." systems within {$within} ly of {$center}.\n";
        // fallback: if DB is too sparse, try EDSM automatically
        if (count($radNames) <= 3) {
            $radNames = names_within_radius_edsm($mysqli, $cx, $cy, $cz, $within, $limit, $center);
            echo "[INFO] (Fallback: EDSM) Selected ".count($radNames)." systems within {$within} ly of {$center}.\n";
        }
    }
    $names = array_merge($names, $radNames);
}

$names = array_values(array_unique(array_filter($names)));

if (empty($names)) {
    fwrite(STDERR,
        "Usage:\n".
        "  php scripts/ingest/backfill_system_info.php \"Sol\" \"Lave\" \"Shinrarta Dezhra\"\n".
        "  php scripts/ingest/backfill_system_info.php --recent=100\n".
        "  php scripts/ingest/backfill_system_info.php --center=\"Sol\" --within=50 [--limit=2000] [--source=db|edsm]\n"
    );
    exit(2);
}

// ---------------- run ----------------
ensure_columns($mysqli, 'edtb_systems');
ensure_columns($mysqli, 'systems');

$ok = 0; $fail = 0;
foreach ($names as $name) {
    $d = edsm_fetch($name);
    if (!$d) { $fail++; echo "[SKIP] ".$name."\n"; continue; }

    upsert_systems($mysqli, 'edtb_systems', $name, $d);
    upsert_systems($mysqli, 'systems',      $name, $d);

    // also persist coords, if provided
    if (!empty($d['coords'])) {
        upsert_coords($mysqli, $name, $d['coords']['x'], $d['coords']['y'], $d['coords']['z']);
    }

    echo "[OK] ".$name." — alleg=".(@$d['allegiance'] !== null ? $d['allegiance'] : 'NULL')
        ." gov=".(@$d['government'] !== null ? $d['government'] : 'NULL')
        ." econ=".(@$d['economy'] !== null ? $d['economy'] : 'NULL')
        ." pop=".(@$d['population'] !== null ? $d['population'] : 'NULL')
        ." fac=".(@$d['faction'] !== null ? $d['faction'] : 'NULL')
        ."\n";
    $ok++;
}

echo "Done. Updated {$ok}, skipped {$fail}.\n";
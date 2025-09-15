<?php
declare(strict_types=1);

/**
 * EDToolbox/getData_logs.php
 * - Works inside existing EDTB environment (Theme.php)
 * - Uses MakeLog with output buffering (no double output)
 * - Avoids warnings by ensuring required fields exist on all rows
 * - Provides $_GET defaults expected by MakeLog (sort + system_name)
 * - Supports range/from/to; hides general/personal when system_name is present
 */

$ROOT = dirname(__DIR__);
require_once $ROOT . '/style/Theme.php'; // should set $mysqli and may set $systemTime

header('Content-Type: text/html; charset=utf-8');

// Ensure the variable exists even if Theme.php didn't define it
$systemTime = $systemTime ?? null;

// ---------- tiny helpers ----------
$param = function ($keys, $default = null) {
    if (!is_array($keys)) $keys = [$keys];
    foreach ($keys as $k) {
        if (isset($_GET[$k]) && $_GET[$k] !== '') return $_GET[$k];
        if (isset($_POST[$k]) && $_POST[$k] !== '') return $_POST[$k];
    }
    return $default;
};

$esc = function (?string $s) use ($mysqli) {
    if (!isset($mysqli) || !$mysqli) return (string)($s ?? '');
    return $mysqli->real_escape_string((string)($s ?? ''));
};

$int = function ($v, int $min, int $max, int $fallback) {
    $n = filter_var($v, FILTER_VALIDATE_INT);
    if ($n === false) return $fallback;
    return max($min, min($max, $n));
};

$render_with_makelog = function ($res, string $scope) {
    if (class_exists('\\EDTB\\Log\\MakeLog')) {
        $mk = new \EDTB\Log\MakeLog();
        // guard: only set if present; access via $GLOBALS to avoid undefined warnings
        if (isset($GLOBALS['systemTime']) && $GLOBALS['systemTime'] !== null) {
            $mk->time_difference = $GLOBALS['systemTime'];
        }
        ob_start();
        $ret = $mk->makeLogEntries($res, $scope);
        $buf = ob_get_clean();
        return ($buf !== '') ? $buf : ((is_string($ret) && $ret !== '') ? $ret : '');
    }
    return null; // caller will fallback-render
};

// ---------- inputs ----------
$systemName = trim((string)$param(['system', 'system_name', 'sys'], ''));
$limit      = $int($param(['limit'], null), 1, 50, 10);

$slog_sort = strtoupper((string)$param(['slog_sort'], 'DESC'));
$glog_sort = strtoupper((string)$param(['glog_sort'], 'DESC'));
$slog_sort = ($slog_sort === 'ASC') ? 'ASC' : 'DESC';
$glog_sort = ($glog_sort === 'ASC') ? 'ASC' : 'DESC';

// Provide the $_GET keys MakeLog expects (prevents “Undefined array key …”)
$_GET['slog_sort']  = $_GET['slog_sort']  ?? $slog_sort;
$_GET['glog_sort']  = $_GET['glog_sort']  ?? $glog_sort;
$_GET['system_name'] = $_GET['system_name'] ?? $systemName;

// Date filters (range OR from/to)
$rangeRaw = trim((string)$param(['range'], ''));
$fromRaw  = trim((string)$param(['from','date_from','start'], ''));
$toRaw    = trim((string)$param(['to','date_to','end'], ''));

$whereDate = [];
if ($rangeRaw !== '') {
    if (preg_match('/^(\\d+)\\s*([dDwWmMyY]?)$/', $rangeRaw, $m)) {
        $n = (int)$m[1];
        $u = strtolower($m[2] ?? 'd');
        $days = $n;
        if ($u === 'w') $days = $n * 7;
        elseif ($u === 'm') $days = $n * 30;
        elseif ($u === 'y') $days = $n * 365;
        $whereDate[] = "user_log.stardate >= (NOW() - INTERVAL " . (int)$days . " DAY)";
    }
}
if ($fromRaw && preg_match('/^\\d{4}-\\d{2}-\\d{2}$/', $fromRaw)) {
    $whereDate[] = "user_log.stardate >= '" . $esc($fromRaw) . " 00:00:00'";
}
if ($toRaw && preg_match('/^\\d{4}-\\d{2}-\\d{2}$/', $toRaw)) {
    $whereDate[] = "user_log.stardate <= '" . $esc($toRaw) . " 23:59:59'";
}
$whereDateSql = $whereDate ? (" AND " . implode(" AND ", $whereDate)) : "";

// If a system is requested, default to NOT showing the general/personal block
$showGeneralBlock = ($systemName === '');

// ---------- output ----------
$outHtml = '';

// ---------- system logs (only when a system is specified) ----------
if ($systemName !== '') {
    $escSystem = $esc($systemName);
    $qSys = "
        SELECT
            user_log.id,
            COALESCE(user_log.system_name, edtb_systems.name) AS system_name,
            user_log.station_id,
            user_log.log_entry,
            user_log.stardate,
            user_log.title,
            user_log.pinned,
            user_log.type,
            user_log.audio,
            edtb_stations.name AS station_name,
            NULL AS distance
        FROM user_log
        LEFT JOIN edtb_systems ON user_log.system_id = edtb_systems.id
        LEFT JOIN edtb_stations ON user_log.station_id = edtb_stations.id
        WHERE user_log.system_name = '$escSystem' AND user_log.type = 'system' $whereDateSql
        ORDER BY -user_log.pinned ASC, user_log.weight, user_log.stardate $slog_sort
        LIMIT $limit
    ";
    $sysRes = @$mysqli->query($qSys);
    if ($sysRes && $sysRes->num_rows > 0) {
        $rendered = $render_with_makelog($sysRes, 'system');
        if (is_string($rendered) && $rendered !== '') {
            $outHtml .= $rendered;
        } else {
            while ($row = $sysRes->fetch_assoc()) {
                $ts    = htmlspecialchars((string)($row['stardate'] ?? ''), ENT_QUOTES, 'UTF-8');
                $title = htmlspecialchars((string)($row['title'] ?? ''), ENT_QUOTES, 'UTF-8');
                $entry = nl2br(htmlspecialchars((string)($row['log_entry'] ?? ''), ENT_QUOTES, 'UTF-8'));
                $sys   = htmlspecialchars((string)($row['system_name'] ?? ''), ENT_QUOTES, 'UTF-8');
                $outHtml .= "<article class=\"log system\"><header><strong>{$title}</strong>"
                         .  " <span class=\"ts\">{$ts}</span> — <span class=\"sys\">{$sys}</span></header>"
                         .  "<div class=\"body\">{$entry}</div></article>\n";
            }
        }
        $sysRes->close();
    }
}

// ---------- general/personal block (optional) ----------
if ($showGeneralBlock) {
    $catRaw = strtolower(trim((string)$param(['category','type'], 'general')));
    $cat = in_array($catRaw, ['general','personal'], true) ? $catRaw : 'general';

    $qGen = "
        SELECT
            id,
            log_entry,
            stardate,
            pinned,
            title,
            audio,
            type,
            NULL AS system_name,
            NULL AS station_name,
            NULL AS distance
        FROM user_log
        WHERE user_log.type = '".$esc($cat)."' $whereDateSql
        ORDER BY -pinned, weight, stardate $glog_sort
        LIMIT " . min($limit, 10);

    $genRes = @$mysqli->query($qGen);
    if ($genRes && $genRes->num_rows > 0) {
        $rendered = $render_with_makelog($genRes, 'general');
        if (is_string($rendered) && $rendered !== '') {
            $outHtml .= $rendered;
        } else {
            while ($row = $genRes->fetch_assoc()) {
                $ts    = htmlspecialchars((string)($row['stardate'] ?? ''), ENT_QUOTES, 'UTF-8');
                $title = htmlspecialchars((string)($row['title'] ?? ''), ENT_QUOTES, 'UTF-8');
                $entry = nl2br(htmlspecialchars((string)($row['log_entry'] ?? ''), ENT_QUOTES, 'UTF-8'));
                $outHtml .= "<article class=\"log general\"><header><strong>{$title}</strong>"
                         .  " <span class=\"ts\">{$ts}</span></header>"
                         .  "<div class=\"body\">{$entry}</div></article>\n";
            }
        }
        $genRes->close();
    }
}

// ---------- finish ----------
echo ($outHtml !== '') ? $outHtml : '<div class="log empty">No log entries yet.</div>';

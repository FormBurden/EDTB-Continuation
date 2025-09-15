<?php
declare(strict_types=1);

/**
 * EDToolbox/getData_logs.php
 * Minimal, Linux-native endpoint for ED ToolBox logs.
 * - Tries to use existing EDTB environment ($mysqli, $settings, $systemTime, MakeLog)
 * - Falls back to simple HTML rendering if helpers/classes aren't available.
 */

$ROOT = dirname(__DIR__);
require_once $ROOT . '/style/Theme.php'; // expected to define $mysqli, $settings, $systemTime

header('Content-Type: text/html; charset=utf-8');

// --- helpers ---------------------------------------------------------------
$param = function ($keys, $default = null) {
    if (!is_array($keys)) $keys = [$keys];
    foreach ($keys as $k) {
        if (isset($_GET[$k]) && $_GET[$k] !== '') return $_GET[$k];
        if (isset($_POST[$k]) && $_POST[$k] !== '') return $_POST[$k];
    }
    return $default;
};

$esc = function (?string $s) use ($mysqli) {
    if (!isset($mysqli) || !$mysqli) return $s ?? '';
    return $mysqli->real_escape_string($s ?? '');
};

$int = function ($v, int $min, int $max, int $fallback) {
    $n = filter_var($v, FILTER_VALIDATE_INT);
    if ($n === false) return $fallback;
    return max($min, min($max, $n));
};

$hasMakeLog = class_exists('\\EDTB\\Log\\MakeLog');

// --- inputs ----------------------------------------------------------------
$systemName = (string)$param(['system', 'system_name', 'sys'], '');
$category   = (string)$param(['category', 'type'], '');
$limit      = $int($param(['limit'], null), 1, 50, 10);

$slog_sort = strtoupper((string)$param(['slog_sort'], 'DESC'));
$glog_sort = strtoupper((string)$param(['glog_sort'], 'DESC'));
$slog_sort = ($slog_sort === 'ASC') ? 'ASC' : 'DESC';
$glog_sort = ($glog_sort === 'ASC') ? 'ASC' : 'DESC';

// --- queries ---------------------------------------------------------------
$outHtml = '';

// 1) System logs (optional; only if system name provided)
if ($systemName !== '') {
    $escSystem = $esc($systemName);
    $qSys = "
        SELECT user_log.id, user_log.system_name AS log_system_name, user_log.station_id,
               user_log.log_entry, user_log.stardate, user_log.title, user_log.pinned,
               user_log.type, user_log.audio,
               edtb_systems.name AS system_name, edtb_stations.name AS station_name
        FROM user_log
        LEFT JOIN edtb_systems ON user_log.system_id = edtb_systems.id
        LEFT JOIN edtb_stations ON user_log.station_id = edtb_stations.id
        WHERE user_log.system_name = '$escSystem'
        ORDER BY -user_log.pinned ASC, user_log.weight, user_log.stardate $slog_sort
        LIMIT $limit
    ";
    $sysRes = @$mysqli->query($qSys);
    if ($sysRes && $sysRes->num_rows > 0) {
        if ($hasMakeLog) {
            $mk = new \EDTB\Log\MakeLog();
            if (isset($systemTime)) $mk->time_difference = $systemTime;
            $outHtml .= $mk->makeLogEntries($sysRes, 'system');
        } else {
            while ($row = $sysRes->fetch_assoc()) {
                $ts = htmlspecialchars($row['stardate'] ?? '');
                $title = htmlspecialchars($row['title'] ?? '');
                $entry = nl2br(htmlspecialchars($row['log_entry'] ?? ''));
                $sys = htmlspecialchars($row['log_system_name'] ?? '');
                $outHtml .= "<article class=\"log system\"><header><strong>$title</strong>"
                         . " <span class=\"ts\">$ts</span> — <span class=\"sys\">$sys</span></header>"
                         . "<div class=\"body\">$entry</div></article>\n";
            }
        }
        $sysRes->close();
    }
}

// 2) General logs (always show a few)
$qGen = "
    SELECT id, log_entry, stardate, pinned, title, audio
    FROM user_log
    WHERE type = 'general'
    ORDER BY -pinned, weight, stardate $glog_sort
    LIMIT " . min($limit, 10);

$genRes = @$mysqli->query($qGen);
if ($genRes && $genRes->num_rows > 0) {
    if ($hasMakeLog) {
        $mk = new \EDTB\Log\MakeLog();
        if (isset($systemTime)) $mk->time_difference = $systemTime;
        $outHtml .= $mk->makeLogEntries($genRes, 'general');
    } else {
        while ($row = $genRes->fetch_assoc()) {
            $ts = htmlspecialchars($row['stardate'] ?? '');
            $title = htmlspecialchars($row['title'] ?? '');
            $entry = nl2br(htmlspecialchars($row['log_entry'] ?? ''));
            $outHtml .= "<article class=\"log general\"><header><strong>$title</strong>"
                     . " <span class=\"ts\">$ts</span></header><div class=\"body\">$entry</div></article>\n";
        }
    }
    $genRes->close();
}

if ($outHtml === '') {
    echo '<div class="log empty">No log entries yet.</div>';
} else {
    echo $outHtml;
}

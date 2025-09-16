<?php
declare(strict_types=1);

/**
 * EDToolbox/getData_logs.php
 * Plain renderer for ED ToolBox logs (no MakeLog).
 * - Stays inside ED ToolBox only
 * - Quiet on PHP 8.4 (suppresses deprecations from other code paths)
 * - Supports: category=general|personal, system_name=..., range=Nd/Nw/Nm/Ny, from=YYYY-MM-DD, to=YYYY-MM-DD
 * - Hides general/personal block when system_name is provided
 */

@ini_set('error_reporting', (string) ((E_ALL & ~E_DEPRECATED) & ~E_USER_DEPRECATED));

$ROOT = dirname(__DIR__);
require_once $ROOT . '/style/Theme.php'; // provides $mysqli

header('Content-Type: text/html; charset=utf-8');

// ---- helpers ---------------------------------------------------------------
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

$h = fn($s) => htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8');

// ---- inputs ----------------------------------------------------------------
$systemName = trim((string)$param(['system','system_name','sys'], ''));
$limit      = $int($param(['limit'], null), 1, 50, 10);

$slog_sort = strtoupper((string)$param(['slog_sort'], 'DESC'));
$glog_sort = strtoupper((string)$param(['glog_sort'], 'DESC'));
$slog_sort = ($slog_sort === 'ASC') ? 'ASC' : 'DESC';
$glog_sort = ($glog_sort === 'ASC') ? 'ASC' : 'DESC';

// range/from/to
$rangeRaw = trim((string)$param(['range'], ''));
$fromRaw  = trim((string)$param(['from','date_from','start'], ''));
$toRaw    = trim((string)$param(['to','date_to','end'], ''));

$whereDate = [];
if ($rangeRaw !== '' && preg_match('/^(\\d+)\\s*([dDwWmMyY]?)$/', $rangeRaw, $m)) {
    $n = (int)$m[1]; $u = strtolower($m[2] ?? 'd'); $days = $n;
    if     ($u === 'w') $days = $n * 7;
    elseif ($u === 'm') $days = $n * 30;
    elseif ($u === 'y') $days = $n * 365;
    $whereDate[] = "user_log.stardate >= (NOW() - INTERVAL " . (int)$days . " DAY)";
}
if ($fromRaw && preg_match('/^\\d{4}-\\d{2}-\\d{2}$/', $fromRaw)) {
    $whereDate[] = "user_log.stardate >= '" . $esc($fromRaw) . " 00:00:00'";
}
if ($toRaw && preg_match('/^\\d{4}-\\d{2}-\\d{2}$/', $toRaw)) {
    $whereDate[] = "user_log.stardate <= '" . $esc($toRaw) . " 23:59:59'";
}
$whereDateSql = $whereDate ? (" AND " . implode(" AND ", $whereDate)) : "";

// general/personal only when NOT filtering by system
$showGeneralBlock = ($systemName === '');

// ---- output ----------------------------------------------------------------
$out = '';

// ---- system logs -----------------------------------------------------------
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
            edtb_stations.name AS station_name
        FROM user_log
        LEFT JOIN edtb_systems ON user_log.system_id = edtb_systems.id
        LEFT JOIN edtb_stations ON user_log.station_id = edtb_stations.id
        WHERE user_log.type = 'system'
          AND user_log.system_name = '$escSystem'
          $whereDateSql
        ORDER BY -user_log.pinned ASC, user_log.weight, user_log.stardate $slog_sort
        LIMIT $limit
    ";
    if ($res = @$mysqli->query($qSys)) {
        if ($res->num_rows > 0) {
            $out .= '<header><h2><img class="icon" src="/style/img/system_log.png" alt="log">System log for '
                 .  '<a href="/System?system_name=' . rawurlencode($systemName) . '">' . $h($systemName) . '</a></h2></header><hr>';
            while ($r = $res->fetch_assoc()) {
            $out .= '<h3>' . $h($r['stardate']) . '&nbsp;&ndash;&nbsp;' . $h($r['title']) . '</h3>'
                    . '<div class="log-meta">'
                    .   '<span class="log-system">' . $h($systemName) . '</span>'
                    .   (!empty($r['station_name']) ? ' &middot; <span class="log-station">' . $h($r['station_name']) . '</span>' : '')
                    .   ' &middot; '
                    .   '<a href="#" data-action="edit" data-log-id="' . $h($r['id']) . '">Edit</a>'
                    .   ' &middot; '
                    .   '<a href="#" data-action="delete" data-log-id="' . $h($r['id']) . '">Delete</a>'
                    . '</div>'
                    . '<pre class="entriespre" style="margin-bottom: 20px">' . nl2br($h($r['log_entry'])) . '</pre>';
        }
    
        }
        $res->close();
    }
}

// ---- general/personal logs -------------------------------------------------
if ($showGeneralBlock) {
    $catRaw = strtolower(trim((string)$param(['category','type'], 'general')));
    $cat = in_array($catRaw, ['general','personal'], true) ? $catRaw : 'general';

    $qGen = "
        SELECT id, log_entry, stardate, pinned, title, audio
        FROM user_log
        WHERE user_log.type = '".$esc($cat)."' $whereDateSql
        ORDER BY -pinned, weight, stardate $glog_sort
        LIMIT " . min($limit, 10);

    if ($res = @$mysqli->query($qGen)) {
        while ($r = $res->fetch_assoc()) {
            $out .= '<h3>' . $h($r['stardate']) . '&nbsp;&ndash;&nbsp;' . $h($r['title']) . '</h3>'
            . '<div class="log-meta">'
            .   '<a href="#" data-action="edit" data-log-id="' . $h($r['id']) . '">Edit</a>'
            .   ' &middot; '
            .   '<a href="#" data-action="delete" data-log-id="' . $h($r['id']) . '">Delete</a>'
            . '</div>'
            . '<pre class="entriespre" style="margin-bottom: 20px">' . nl2br($h($r['log_entry'])) . '</pre>';
        }
        $res->close();
    }
}

// ---- finish ----------------------------------------------------------------
echo ($out !== '') ? $out : '<div class="log empty">No log entries yet.</div>';

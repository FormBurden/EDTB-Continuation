<?php
declare(strict_types=1);

// // source/config.inc.php  (near the top)
// define('APP_ROOT', dirname(__DIR__));                          // repo root
// define('SRC_DIR',  APP_ROOT . '/source');                      // src
// define('DATA_DIR', APP_ROOT . '/source/data');                 // INI lives here
// define('ETDB_DATA_DIR', APP_ROOT . '/data');                   // CSV/server_config lives here

// // If you include server_config.inc.php anywhere, do it PORTABLY:
// require_once ETDB_DATA_DIR . '/server_config.inc.php';


// // Load our Linux/server settings (relative, not /data)
// $server = include __DIR__ . '/../data/server_config.inc.php';

// // Optional convenience vars/constants used elsewhere
// $INSTALL_PATH = $server['install_path'] ?? dirname(__DIR__);
// $DATA_DIR     = $server['data_dir']     ?? __DIR__ . '/../data';

// if (!defined('EDTB_INSTALL_PATH')) define('EDTB_INSTALL_PATH', $INSTALL_PATH);
// if (!defined('EDTB_DATA_DIR'))     define('EDTB_DATA_DIR', $DATA_DIR);

/**
 * Portable path bootstrap for EDTB
 * - Works on native Linux and Docker
 * - Never hardcodes "/data"; only uses it if it actually exists
 */

// Repo root (this file is in /source)
$EDTB_ROOT = dirname(__DIR__);

// Discover data dir in this preference order:
//  1) .env  (EDTB_DATA_DIR) — supports quoted paths with spaces
//  2) repo-root "/data"
//  3) legacy "source/data"
//  4) docker-style absolute "/data" (ONLY if it exists)
$envData = getenv('EDTB_DATA_DIR') ?: null;
$candidates = array_filter([
    $envData,
    $EDTB_ROOT . '/data',
    $EDTB_ROOT . '/source/data',
    '/data',
]);

$EDTB_DATA = null;
foreach ($candidates as $cand) {
    if ($cand && is_dir($cand)) { $EDTB_DATA = rtrim($cand, '/'); break; }
}
if ($EDTB_DATA === null) {
    // Last-resort safety: create a repo-local data folder
    $EDTB_DATA = $EDTB_ROOT . '/data';
    if (!is_dir($EDTB_DATA)) {
        @mkdir($EDTB_DATA, 0775, true);
    }
}

// Make them globally available
if (!defined('EDTB_ROOT')) define('EDTB_ROOT', $EDTB_ROOT);
if (!defined('EDTB_DATA')) define('EDTB_DATA', $EDTB_DATA);

// Default server config values; will be overridden if a file is found.
$server_config = [
    'install_path' => EDTB_ROOT,
    'data_dir'     => EDTB_DATA,
];

// ---------------------------------------------------------
// phpFastCache: portable file-cache location (Linux/Windows)
// ---------------------------------------------------------
// Resolve the project data directory used elsewhere in this file.
// We support either EDTB_DATA or EDTB_DATA_DIR, falling back to repo /data.
$__edtbData = defined('EDTB_DATA')
    ? EDTB_DATA
    : (defined('EDTB_DATA_DIR')
        ? EDTB_DATA_DIR
        : (isset($EDTB_ROOT) ? ($EDTB_ROOT . '/data') : (dirname(__DIR__) . '/data')));

// Define a cache dir under data/
if (!defined('EDTB_CACHE_DIR')) {
    define('EDTB_CACHE_DIR', rtrim($__edtbData, '/\\') . '/cache');
}

// Ensure it exists (don’t fatal if it can’t be created)
if (!is_dir(EDTB_CACHE_DIR)) {
    @mkdir(EDTB_CACHE_DIR, 0775, true);
}

// Configure phpFastCache if/when the class is available
if (class_exists('phpFastCache')) {
    phpFastCache::setup('storage', 'files');
    phpFastCache::setup('path', EDTB_CACHE_DIR);
    // Keep a single cache root name under the path (avoids per-host subdirs)
    phpFastCache::setup('securityKey', 'phpfastcache');
}


// Load server_config.inc.php from the first place it exists.
// (NEVER fatal if missing; we keep $server_config defaults.)
$serverCfgCandidates = [
    EDTB_DATA . '/server_config.inc.php',
    $EDTB_ROOT . '/data/server_config.inc.php',
    '/data/server_config.inc.php',
];
foreach ($serverCfgCandidates as $cfg) {
    if (is_file($cfg)) {
        /** @noinspection PhpIncludeInspection */
        require_once $cfg;
        break;
    }
}

// Normalize values if the file defined constants instead of array.
if (defined('INSTALL_PATH') && empty($server_config['install_path'])) {
    $server_config['install_path'] = (string) INSTALL_PATH;
}
if (defined('DATA_DIR') && empty($server_config['data_dir'])) {
    $server_config['data_dir'] = (string) DATA_DIR;
}

// Keep some helpful constants for the rest of the codebase.
if (!defined('INSTALL_PATH')) define('INSTALL_PATH', $server_config['install_path']);
if (!defined('DATA_DIR'))    define('DATA_DIR', $server_config['data_dir']);
// === Canonical UI defaults (Linux port) ===
// Ensure essential UI keys exist (no per-call guards scattered around).
if (!isset($settings) || !is_array($settings)) {
    $settings = [];
}

// Only set when missing (do not override existing values).
$settings['edtb_version']     = $settings['edtb_version']     ?? 'DEV';
$settings['cmdr_name']        = $settings['cmdr_name']        ?? 'CMDR';
$settings['game_time']        = $settings['game_time']        ?? '00:00:00';
$settings['show_now_playing'] = $settings['show_now_playing'] ?? 'false';

if (!defined('GALNET_FEED')) {
    // Frontier GalNet JSON (newest first, 12 items)
    define('GALNET_FEED', 'https://cms.zaonce.net/en-GB/jsonapi/node/galnet_article?sort=-published_at&page[offset]=0&page[limit]=12');
}

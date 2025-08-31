<?php
declare(strict_types=1);

// Load our Linux/server settings (relative, not /data)
$server = include __DIR__ . '/../data/server_config.inc.php';

// Optional convenience vars/constants used elsewhere
$INSTALL_PATH = $server['install_path'] ?? dirname(__DIR__);
$DATA_DIR     = $server['data_dir']     ?? __DIR__ . '/../data';

if (!defined('EDTB_INSTALL_PATH')) define('EDTB_INSTALL_PATH', $INSTALL_PATH);
if (!defined('EDTB_DATA_DIR'))     define('EDTB_DATA_DIR', $DATA_DIR);

/**
 * Legacy compatibility bootstrap for EDTB on Linux.
 * - Loads Composer/.env via our modern Config.
 * - Exposes both PDO ($pdo) and mysqli ($mysqli) for old code paths.
 * - Provides $install_path, $data_path, and a few legacy globals/consts.
 * - Safe to include multiple times.
 */

if (!defined('EDTB_BOOTSTRAPPED')) {
    define('EDTB_BOOTSTRAPPED', true);

    // Composer autoload (project root/vendor)
    $PROJECT_ROOT = realpath(__DIR__ . '/..') ?: dirname(__DIR__);
    require_once $PROJECT_ROOT . '/vendor/autoload.php';

    // Modern config / DB
    $config = \EDTB\Config::fromEnv($PROJECT_ROOT);
    $pdo    = \EDTB\DB::connect($config)->pdo();

    // Legacy mysqli handle (many old scripts use ->query()->fetch_object())
    mysqli_report(MYSQLI_REPORT_OFF);
    $mysqli = @new mysqli($config->dbHost, $config->dbUser, $config->dbPass, $config->dbName, $config->dbPort);
    if ($mysqli->connect_errno) {
        // Keep legacy-style fatal so old code doesn't continue silently
        die('EDTB: DB connection failed (mysqli): ' . $mysqli->connect_error);
    }
    $mysqli->set_charset('utf8mb4');

    // Legacy path variables expected by old code
    $install_path = rtrim($config->rootDir, '/');  // often referenced as install_path
    $data_path    = rtrim($config->dataDir, '/');  // often referenced as data path

    // Common constants some pages used
    if (!defined('EDTB_ROOT')) define('EDTB_ROOT', $install_path);
    if (!defined('EDTB_DATA')) define('EDTB_DATA', $data_path);
    if (!defined('EDTB_BASE_URL')) define('EDTB_BASE_URL', $config->baseUrl);

    // Legacy-style arrays some scripts read (defensive: both $CONFIG and $configArr)
    $CONFIG = [
        'install_path' => $install_path,
        'root_dir'     => $install_path,
        'data_dir'     => $data_path,
        'base_url'     => $config->baseUrl,
        'db' => [
            'host' => $config->dbHost,
            'port' => $config->dbPort,
            'name' => $config->dbName,
            'user' => $config->dbUser,
            'pass' => $config->dbPass,
        ],
    ];
    $configArr = $CONFIG; // in case some files used a different variable name

    // Make globals available like legacy scripts expect
    $GLOBALS['pdo']    = $pdo;
    $GLOBALS['mysqli'] = $mysqli;
    $GLOBALS['CONFIG'] = $CONFIG;

    // Sessions/timezone (safe defaults for legacy UI bits)
    if (function_exists('date_default_timezone_set')) {
        @date_default_timezone_set('UTC');
    }
    if (session_status() === PHP_SESSION_NONE) {
        @session_start();
    }

    // Helper getters (optional)
    if (!function_exists('edtb_pdo')) {
        function edtb_pdo(): \PDO { return $GLOBALS['pdo']; }
    }
    if (!function_exists('edtb_mysqli')) {
        function edtb_mysqli(): \mysqli { return $GLOBALS['mysqli']; }
    }
}

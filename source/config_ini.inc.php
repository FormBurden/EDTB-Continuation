<?php
/**
 * Config file
 *
 * No description
 *
 * @package EDTB\Backend
 * @author Mauri Kujala <contact@edtb.xyz>
 * @copyright Copyright (C) 2016, Mauri Kujala
 * @license http://www.gnu.org/licenses/old-licenses/gpl-2.0.html GNU Public License version 2
 */

 /*
 * ED ToolBox, a companion web app for the video game Elite Dangerous
 * (C) 1984 - 2016 Frontier Developments Plc.
 * ED ToolBox or its creator are not affiliated with Frontier Developments Plc.
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 2
 * of the License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301, USA
 */

/**
 * Register project-specific autoloader for classes, adapted from the PSR-4 example file
 *
 * @param string $class The fully-qualified class name.
 * @return void
 */
spl_autoload_register(function ($class) {
    /** @var string $prefix project-specific namespace prefix */
    $prefix = 'EDTB\\';

    /** @var string $baseDir base directory for the namespace prefix */
    $baseDir = $_SERVER['DOCUMENT_ROOT'] . '/';

    // does the class use the namespace prefix?
    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) {
        // no, move to the next registered autoloader
        return;
    }

    // get the relative class name
    $relativeClass = substr($class, $len);

    // replace the namespace prefix with the base directory, replace namespace
    // separators with directory separators in the relative class name, append
    // with .php
    $file = $baseDir . str_replace('\\', '/', $relativeClass) . '.php';

    // if the file exists, require it
    if (file_exists($file)) {
        require $file;
    }
});

// Always define dir + path
$iniDir  = __DIR__ . '/data';
$iniPath = $iniDir . '/edtoolbox_v1.ini';

// Create a safe default INI if missing
if (!is_file($iniPath)) {
    @mkdir($iniDir, 0775, true);
    file_put_contents($iniPath, <<<INI
[database]
host="127.0.0.1"
name="edtb"
user="edtb"
pass="edtbpass"
port=3306

[paths]
log_dir="/mnt/Unlimited-Gaming/SteamLibrary/steamapps/compatdata/359320/pfx/drive_c/users/steamuser/Saved Games/Frontier Developments/Elite Dangerous"
screens_dir="/mnt/Unlimited-Gaming/SteamLibrary/steamapps/compatdata/359320/pfx/drive_c/users/steamuser/Pictures/Frontier Developments/Elite Dangerous"
data_dir="/mnt/Unlimited-Gaming/Modding/Elite Dangerous/MyProject/EDTB-Continuation/data"

[settings]
edtb_version="dev"
default_map="system_map"
cmdr_name=""
game_time="UTC"
ext_links=""
INI);
}

// Be lenient with odd values (use RAW)
$ini = parse_ini_file($iniPath, true, INI_SCANNER_RAW) ?: [];

// Bridge values to your PHP config array ($server)
$settings = [
    'db_host'     => $ini['database']['host']   ?? ($server['db_host'] ?? '127.0.0.1'),
    'db_name'     => $ini['database']['name']   ?? ($server['db_name'] ?? 'edtb'),
    'db_user'     => $ini['database']['user']   ?? ($server['db_user'] ?? 'edtb'),
    'db_pass'     => $ini['database']['pass']   ?? ($server['db_pass'] ?? 'edtbpass'),
    'db_port'     => (int)($ini['database']['port'] ?? ($server['db_port'] ?? 3306)),
    'log_dir'     => $ini['paths']['log_dir']   ?? ($server['netlog_dir']  ?? null),
    'screens_dir' => $ini['paths']['screens_dir'] ?? ($server['screens_dir'] ?? null),
    'data_dir'    => $ini['paths']['data_dir']  ?? ($server['data_dir']    ?? (__DIR__.'/../data')),
    'edtb_version'=> $ini['settings']['edtb_version'] ?? 'dev',
    'default_map' => $ini['settings']['default_map']  ?? 'system_map',
    'cmdr_name'   => $ini['settings']['cmdr_name']    ?? 'CMDR',
    'game_time'   => $ini['settings']['game_time']    ?? 'UTC',
    'ext_links'   => $ini['settings']['ext_links']    ?? [],
];

/**
 * set the new screendir if it's empty
 */
$settings['new_screendir'] = empty($settings['new_screendir']) ? $iniDir . '/EDTB/screenshots' : $settings['new_screendir'];

// --- Linux: guess Elite Dangerous paths under Proton and set sane defaults ---
// NOTE: These defaults only apply if a value was not already provided in INI or DB.

$home = rtrim(getenv('HOME') ?: '', '/');

// Common Proton locations for Elite Dangerous (Steam app 359320)
$protonUser   = $home . '/.steam/steam/steamapps/compatdata/359320/pfx/drive_c/users/steamuser';
$protonSaved  = $protonUser . '/Saved Games/Frontier Developments/Elite Dangerous';
$protonShots  = $protonSaved . '/Screenshots';

// If the guessed folders exist, prefer them; otherwise fall back to EDTB-managed folder
if (empty($settings['elite_saved_games_dir']) || !is_dir($settings['elite_saved_games_dir'])) {
    $settings['elite_saved_games_dir'] = is_dir($protonSaved)
        ? $protonSaved
        : $settings['install_path'] . '/EDTB/saved-games';
}

if (empty($settings['elite_screenshots_dir']) || !is_dir($settings['elite_screenshots_dir'])) {
    $settings['elite_screenshots_dir'] = is_dir($protonShots)
        ? $protonShots
        : $settings['new_screendir'];
}

// Backwards compat: if the legacy Gallery “old_screendir” isn’t set on Linux, make it useful.
// This replaces the Windows C:\ path checks we removed earlier.
if (empty($settings['old_screendir']) || !is_dir($settings['old_screendir'])) {
    $settings['old_screendir'] = $settings['elite_screenshots_dir'];
}

// Ensure the EDTB-managed directories exist so uploads/moves don’t fail.
foreach ([
    $settings['install_path'] . '/cache',
    $settings['install_path'] . '/EDTB',
    $settings['new_screendir'],
] as $mustDir) {
    if (!is_dir($mustDir)) {
        @mkdir($mustDir, 0755, true);
    }
}


$settings['cookie_file'] = $settings['install_path'] . '/cache/cookies';

$settings['curl_exe'] = '/usr/bin/curl';

$profileFile = $settings['install_path'] . '/cache/profile.json';
// Ensure cache directory exists on Linux
if (!is_dir($settings['install_path'] . '/cache')) {
    @mkdir($settings['install_path'] . '/cache', 0755, true);
}

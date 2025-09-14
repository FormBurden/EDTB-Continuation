<?php
/**
 * ED ToolBox - Current System resolver (Linux-native rewrite)
 * Resolves the current system from ED Journal (preferred) or legacy netLog,
 * pulls DB details, and exposes $curSys + $newSystem for get/getData.php.
 *
 * No defensive “guards” added beyond what’s necessary to run.
 */

require_once __DIR__ . '/config.inc.php';
require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/MySQL.php'; // expects $mysqli
// $settings is provided by config.inc.php

$curSys    = [];
$newSystem = false;

/**
 * 1) Try to resolve from Journal.*.log (modern ED logs)
 */
$logDir = rtrim($settings['log_dir'] ?? '', DIRECTORY_SEPARATOR);

if ($logDir !== '' && is_dir($logDir) && is_readable($logDir)) {
    $journalMatches = glob($logDir . DIRECTORY_SEPARATOR . 'Journal.*.log', GLOB_NOSORT) ?: [];

    $lineCandidates = [];

    if (!empty($journalMatches)) {
        natsort($journalMatches);
        $latestJournal = end($journalMatches);

        $raw = @file($latestJournal, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        if ($raw !== false) {
            // Walk newest → oldest to find Location/FSDJump/CarrierJump first
            for ($i = count($raw) - 1; $i >= 0; $i--) {
                $row = $raw[$i];
                $j   = json_decode($row, true);
                if (!is_array($j)) {
                    continue;
                }
                if (isset($j['StarSystem'], $j['StarPos']) && is_array($j['StarPos']) && count($j['StarPos']) === 3) {
                    $tsISO  = $j['timestamp'] ?? null;
                    $tsUnix = $tsISO ? strtotime($tsISO) : time();
                    $visitedTime = date('H:i:s', $tsUnix);

                    // Synthesize a “netlog-like” parse target to reuse parsing below
                    $lineCandidates[] =
                        '{' . $visitedTime . '} System:"' . $j['StarSystem'] . '" StarPos:(' .
                        $j['StarPos'][0] . ',' . $j['StarPos'][1] . ',' . $j['StarPos'][2] . ')';
                    break;
                }
            }
        }
    }

    /**
     * 2) Fallback to legacy netLog if Journal didn’t give us a hit
     */
    if (empty($lineCandidates)) {
        $dirScan = scandir($logDir, SCANDIR_SORT_DESCENDING);
        if ($dirScan) {
            $newest = $dirScan[0];
            $netRaw = @file($logDir . DIRECTORY_SEPARATOR . $newest);
            if ($netRaw !== false) {
                $lineCandidates = array_reverse($netRaw);
            }
        }
    }

    /**
     * 3) Parse the first suitable candidate
     */
    foreach ($lineCandidates as $line) {
        $pSystem = strpos($line, 'System:');
        $isCQC   = strrpos($line, 'ProvingGround') !== false;

        if ($pSystem === false || $isCQC) {
            continue;
        }

        // System name
        preg_match_all('/\System:"(.*?)"/', $line, $mName);
        $cssystemname   = $mName[1][0] ?? '';

        // Visit time (local)
        preg_match_all('/\{(.*?)\} System:/', $line, $mTime);
        $visitedTime    = $mTime[1][0] ?? date('H:i:s');

        // Coordinates
        preg_match_all('/\StarPos:\((.*?)\)/', $line, $mPos);
        $coordStr       = $mPos[1][0] ?? '';
        $coordParts     = $coordStr !== '' ? explode(',', $coordStr) : [null, null, null];

        $curSys['name']        = $cssystemname;
        $curSys['esc_name']    = $mysqli->real_escape_string($cssystemname);
        $curSys['coordinates'] = $coordStr;
        $curSys['x']           = $coordParts[0] ?? null;
        $curSys['y']           = $coordParts[1] ?? null;
        $curSys['z']           = $coordParts[2] ?? null;

        // Defaults (kept to mimic Windows side fields)
        $curSys['id']            = -1;
        $curSys['population']    = '';
        $curSys['allegiance']    = '';
        $curSys['economy']       = '';
        $curSys['government']    = '';
        $curSys['ruling_faction']= '';
        $curSys['state']         = 'unknown';
        $curSys['security']      = 'unknown';
        $curSys['power']         = '';
        $curSys['power_state']   = '';
        $curSys['needs_permit']  = '';
        $curSys['updated_at']    = '';
        $curSys['simbad_ref']    = '';
        $curSys['users_own']     = false;

        // Pull DB system row (use SELECT * to avoid column mismatch errors across migrations)
        $sysName = $mysqli->real_escape_string($curSys['name']);
        $qSys    = "SELECT * FROM edtb_systems WHERE name = '$sysName' LIMIT 1";
        if ($res = $mysqli->query($qSys)) {
            if ($res->num_rows > 0) {
                $row = $res->fetch_object();

                // Latched core fields we actually use
                $curSys['id']          = (int)($row->id ?? -1);
                $curSys['x']           = $row->x ?? $curSys['x'];
                $curSys['y']           = $row->y ?? $curSys['y'];
                $curSys['z']           = $row->z ?? $curSys['z'];
                $curSys['coordinates'] = $curSys['x'] . ',' . $curSys['y'] . ',' . $curSys['z'];

                $curSys['population']     = $row->population     ?? '';
                $curSys['allegiance']     = $row->allegiance     ?? '';
                $curSys['economy']        = $row->economy        ?? '';
                $curSys['government']     = $row->government     ?? '';
                $curSys['ruling_faction'] = $row->ruling_faction ?? '';
                $curSys['state']          = $row->state          ?? 'unknown';
                $curSys['security']       = $row->security       ?? 'unknown';
                $curSys['power']          = $row->power          ?? '';
                $curSys['power_state']    = $row->power_state    ?? '';
                $curSys['needs_permit']   = $row->needs_permit   ?? '';
                $curSys['updated_at']     = $row->updated_at     ?? '';
                $curSys['simbad_ref']     = $row->simbad_ref     ?? '';
            }
            $res->close();
        }

        /**
         * If not in edtb_systems, keep a stub in user_systems_own
         * (This mirrors Windows behavior and prevents unintended EDSM exports)
         */
        if ($curSys['id'] === -1) {
            $qOwn = "SELECT x,y,z FROM user_systems_own WHERE name = '$sysName' LIMIT 1";
            if ($rOwn = $mysqli->query($qOwn)) {
                if ($rOwn->num_rows > 0) {
                    $o = $rOwn->fetch_object();
                    // Only adopt coordinates if we didn’t read them from logs
                    if ($curSys['x'] === null || $curSys['y'] === null || $curSys['z'] === null) {
                        $curSys['x'] = $o->x;
                        $curSys['y'] = $o->y;
                        $curSys['z'] = $o->z;
                        $curSys['coordinates'] = $curSys['x'] . ',' . $curSys['y'] . ',' . $curSys['z'];
                    }
                    $curSys['users_own'] = true;
                }
                $rOwn->close();
            }

            // Still not found anywhere? Insert baseline into user_systems_own.
            if ($curSys['users_own'] === false) {
                $qx = $curSys['x'] !== null ? "'" . $mysqli->real_escape_string($curSys['x']) . "'" : 'NULL';
                $qy = $curSys['y'] !== null ? "'" . $mysqli->real_escape_string($curSys['y']) . "'" : 'NULL';
                $qz = $curSys['z'] !== null ? "'" . $mysqli->real_escape_string($curSys['z']) . "'" : 'NULL';

                $stmt = "
                    INSERT INTO user_systems_own (name, x, y, z)
                    VALUES ('{$curSys['esc_name']}', $qx, $qy, $qz)
                ";
                $mysqli->query($stmt) or write_log($mysqli->error, __FILE__, __LINE__);
            }
        }

        /**
         * Track visited systems + update last_system “ini” value
         */
        $lastSystem = edtbCommon('last_system', 'value');

        if ($lastSystem !== $cssystemname && $cssystemname !== '') {
            // Prevent duplicate in a tight loop
            $qLast = "SELECT system_name FROM user_visited_systems ORDER BY id DESC LIMIT 1";
            if ($rLast = $mysqli->query($qLast)) {
                $lastRow = $rLast->fetch_object();
                $rLast->close();

                if (!$lastRow || $lastRow->system_name !== $curSys['name']) {
                    $visitedOn = date('Y-m-d') . ' ' . $visitedTime;
                    $qInsert   = "
                        INSERT INTO user_visited_systems (system_name, visit)
                        VALUES ('$sysName', '$visitedOn')
                    ";
                    $mysqli->query($qInsert) or write_log($mysqli->error, __FILE__, __LINE__);

                    // Keep user_systems_own coords up-to-date from logs when present
                    if ($curSys['users_own'] === false && $curSys['x'] !== null) {
                        $qUpd = "
                            UPDATE user_systems_own
                            SET x = '{$curSys['x']}', y = '{$curSys['y']}', z = '{$curSys['z']}'
                            WHERE name = '{$curSys['esc_name']}'
                        ";
                        $mysqli->query($qUpd) or write_log($mysqli->error, __FILE__, __LINE__);
                    }

                    $newSystem = true;
                }
            }

            edtbCommon('last_system', 'value', true, $curSys['name']);
        }

        /**
         * Optional EDSM export — mirrors Windows behavior
         */
        if (
            ($settings['edsm_api_key'] ?? '') !== '' &&
            ($settings['edsm_export']   ?? '') === 'true' &&
            ($settings['edsm_cmdr_name']?? '') !== '' &&
            $curSys['users_own'] === false
        ) {
            // Convert local clock visit time to UTC on today’s date
            $visitedParts = explode(':', $visitedTime);
            $utcNow = new DateTime('now', new DateTimeZone('UTC'));
            $utcNow->setTime((int)$utcNow->format('G'), (int)$visitedParts[1], (int)$visitedParts[2]);
            $visitedUTC = $utcNow->format('Y-m-d H:i:s');

            $exportData = [
                'commanderName'     => $settings['edsm_cmdr_name'],
                'apiKey'            => $settings['edsm_api_key'],
                'systemName'        => $curSys['name'],
                'dateVisited'       => $visitedUTC,
                'fromSoftwareVersion'=> $settings['edtb_version'],
                'fromSoftware'      => 'ED ToolBox',
                'x' => $curSys['x'], 'y' => $curSys['y'], 'z' => $curSys['z'],
            ];
            $exportURL = 'https://www.edsm.net/api-logs-v1/set-log?' . http_build_query($exportData);
            $response  = @file_get_contents($exportURL);
            if ($response) {
                $obj = json_decode($response);
                if (!isset($obj->{'msgnum'}) || (string)$obj->{'msgnum'} !== '100') {
                    write_log($response, __FILE__, __LINE__);
                }
            } else {
                write_log('EDSM export failed', __FILE__, __LINE__);
            }
        }

        // We’ve handled one entry; stop here.
        break;
    }
}

/**
 * Final resolution chain if no log source matched:
 *   1) Latest from user_visited_systems (journal-derived)
 *   2) edtbCommon('last_system')
 *   3) 'Sol'
 */
if (empty($curSys) || empty($curSys['name'])) {
    $latestName = '';
    if ($resUv = $mysqli->query("SELECT system_name FROM user_visited_systems WHERE system_name <> '__INIT__' ORDER BY id DESC LIMIT 1")) {
        if ($rowUv = $resUv->fetch_object()) {
            $latestName = (string)$rowUv->system_name;
        }
        $resUv->close();
    }

    if ($latestName === '') {
        $latestName = edtbCommon('last_system', 'value');
    }
    if ($latestName === '' || $latestName === null) {
        $latestName = 'Sol';
    }

    $curSys['name']     = $latestName;
    $curSys['esc_name'] = $mysqli->real_escape_string($curSys['name']);
    $curSys['id']       = -1;
    $curSys['population'] = '';
    $curSys['allegiance'] = '';
    $curSys['economy']    = '';
    $curSys['government'] = '';
    $curSys['ruling_faction']= '';
    $curSys['state']         = 'unknown';
    $curSys['security']      = 'unknown';
    $curSys['power']         = '';
    $curSys['power_state']   = '';
    $curSys['needs_permit']  = '';
    $curSys['updated_at']    = '';
    $curSys['simbad_ref']    = '';
    $curSys['users_own']     = false;

    // Try to get coordinates from edtb_systems for the fallback
    $sysName = $mysqli->real_escape_string($curSys['name']);
    $q = "SELECT id, x, y, z FROM edtb_systems WHERE name = '$sysName' LIMIT 1";
    if ($r = $mysqli->query($q)) {
        if ($row = $r->fetch_object()) {
            $curSys['id'] = (int)$row->id;
            $curSys['x']  = $row->x;
            $curSys['y']  = $row->y;
            $curSys['z']  = $row->z;
            $curSys['coordinates'] = $curSys['x'] . ',' . $curSys['y'] . ',' . $curSys['z'];
        }
        $r->close();
    }
}


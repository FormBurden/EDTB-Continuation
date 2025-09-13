<?php
declare(strict_types=1);
namespace EDTB\Journal;

/**
 * Journal Parser — Linux-native ingestion for Elite Dangerous journals (Proton/Steam Deck friendly).
 * Writes to:
 *   - user_visited_systems(system_name, visit)
 *   - user_log(system_id NULL, system_name, log_entry JSON, stardate, type='system')
 *
 * State file: {DATA_DIR}/journal_state.json  (last processed file + byte offset + last system)
 *
 * Autoload style: standalone — includes source/config.inc.php and source/MySQL.php itself.
 */
final class Parser
{
    /** Entry point. Safe to call on every request. Lightweight: processes only new bytes from the newest Journal file. */
    public static function ingest(): void
    {
        // Bootstrap settings + DB (mysqli)
        $root = dirname(__DIR__, 1);
        require_once $root . '/config.inc.php'; // defines $settings
        require_once $root . '/MySQL.php';       // defines $mysqli (mysqli)

        if (!isset($settings) || !isset($mysqli) || !($mysqli instanceof \mysqli)) {
            return;
        }

        $journalDir = self::resolveJournalDir($settings);
        if ($journalDir === null) {
            return;
        }

        // Load state
        $dataDir = rtrim((string)($settings['data_dir'] ?? ($root . '/../data')), DIRECTORY_SEPARATOR);
        if ($dataDir === '') { $dataDir = $root . '/../data'; }
        @mkdir($dataDir, 0775, true);
        $stateFile = $dataDir . '/journal_state.json';
        $state = self::loadState($stateFile);

        $latest = self::latestJournal($journalDir);
        if ($latest === null) {
            return;
        }

        $fp = @fopen($latest, 'r');
        if (!$fp) {
            return;
        }

        $offset = 0;
        if (($state['file'] ?? '') === $latest && isset($state['offset']) && is_int($state['offset'])) {
            $offset = max(0, $state['offset']);
        } else {
            // If switching to a new file (new session), start near the end but allow catching last ~8KB
            $offset = 0;
        }

        if ($offset > 0) {
            fseek($fp, $offset);
        }

        $insVisited = $mysqli->prepare('INSERT INTO user_visited_systems (system_name, visit) VALUES (?, ?)');
        $insLog = $mysqli->prepare('INSERT INTO user_log (system_id, system_name, log_entry, stardate, title, pinned, type, audio, weight) VALUES (NULL, ?, ?, ?, NULL, 0, "system", NULL, 0)');
        if (!$insVisited || !$insLog) {
            fclose($fp);
            return;
        }

        $lastSys = (string)($state['last_system'] ?? '');
        $lastTs  = (string)($state['last_ts'] ?? ''); // ISO 8601 from journal

        $processed = 0;
        while (!feof($fp)) {
            $line = fgets($fp);
            if ($line === false) { break; }
            $line = trim($line);
            if ($line === '') { continue; }

            $json = json_decode($line, true);
            if (!is_array($json)) { continue; }

            $event = (string)($json['event'] ?? '');
            $stamp = (string)($json['timestamp'] ?? ''); // ISO UTC
            if ($stamp === '') { continue; }

            // Route only the events we care about
            if ($event === 'FSDJump' || $event === 'Location' || $event === 'CarrierJump') {
                $system = (string)($json['StarSystem'] ?? ($json['System'] ?? ''));
                if ($system !== '') {
                    // Avoid double-inserting the same (system, timestamp) as last processed
                    if (!($system === $lastSys && $stamp === $lastTs)) {
                        // user_visited_systems
                        $insVisited->bind_param('ss', $system, self::toMysqlDt($stamp));
                        @$insVisited->execute();

                        // user_log (raw JSON)
                        $raw = json_encode($json, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
                        $stardate = self::toMysqlDt($stamp);
                        $insLog->bind_param('sss', $system, $raw, $stardate);
                        @$insLog->execute();

                        $lastSys = $system;
                        $lastTs  = $stamp;
                    }
                }
            }

            $processed++;
            // Safety cap per request to keep this lightweight
            if ($processed >= 2000) { break; }
        }

        $newOffset = ftell($fp);
        fclose($fp);

        // Save state
        self::saveState($stateFile, [
            'file' => $latest,
            'offset' => $newOffset,
            'last_system' => $lastSys,
            'last_ts' => $lastTs,
            'saved_at' => gmdate('c'),
        ]);
    }

    /** Resolve the Journal directory using explicit INI first, then common Proton/Steam Deck paths. */
    private static function resolveJournalDir(array $settings): ?string
    {
        $candidates = [];
        $fromIni = trim((string)($settings['log_dir'] ?? ''));
        if ($fromIni !== '') { $candidates[] = $fromIni; }

        $home = rtrim(getenv('HOME') ?: '', DIRECTORY_SEPARATOR);
        if ($home !== '') {
            // Native Steam
            $candidates[] = $home . '/.local/share/Steam/steamapps/compatdata/359320/pfx/drive_c/users/steamuser/Saved Games/Frontier Developments/Elite Dangerous';
            // Flatpak Steam
            $candidates[] = $home . '/.var/app/com.valvesoftware.Steam/.local/share/Steam/steamapps/compatdata/359320/pfx/drive_c/users/steamuser/Saved Games/Frontier Developments/Elite Dangerous';
            // Legendary/Heroic default wine prefixes (best-effort)
            $candidates[] = $home . '/Games/EliteDangerous/pfx/drive_c/users/steamuser/Saved Games/Frontier Developments/Elite Dangerous';
        }

        foreach ($candidates as $dir) {
            if ($dir !== '' && is_dir($dir) && is_readable($dir)) {
                return $dir;
            }
        }
        return null;
    }

    /** Find the newest Journal.*.log file. */
    private static function latestJournal(string $dir): ?string
    {
        $matches = glob(rtrim($dir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'Journal.*.log', GLOB_NOSORT) ?: [];
        if (empty($matches)) { return null; }
        // Natural sort and pick last
        natsort($matches);
        $latest = end($matches);
        return is_string($latest) ? $latest : null;
    }

    /** Load state JSON. */
    private static function loadState(string $file): array
    {
        $raw = @file_get_contents($file);
        if ($raw === false || $raw === '') return [];
        $j = json_decode($raw, true);
        return is_array($j) ? $j : [];
    }

    /** Save state JSON. */
    private static function saveState(string $file, array $state): void
    {
        @file_put_contents($file, json_encode($state, JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE), LOCK_EX);
    }

    /** Convert Journal ISO8601 (UTC) to MySQL DATETIME. Journal uses "YYYY-MM-DDTHH:MM:SSZ". */
    private static function toMysqlDt(string $iso): string
    {
        // Strip trailing Z if present
        if (str_ends_with($iso, 'Z')) { $iso = substr($iso, 0, -1); }
        // Replace T with space
        $iso = str_replace('T', ' ', $iso);
        // Now it's "YYYY-MM-DD HH:MM:SS"
        return $iso;
    }
}

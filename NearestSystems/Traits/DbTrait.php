<?php
/** Auto-extracted trait from NearestSystems.php to reduce file size. */
trait NearestSystemsDbTrait
{
    private function ensureDbSelected(): void
    {
        if (!($this->mysqli instanceof \mysqli)) {
            return;
        }

        // Already selected?
        if ($probe = @$this->mysqli->query('SELECT DATABASE() AS db')) {
            if ($row = $probe->fetch_object()) {
                if (!empty($row->db)) {
                    $probe->close();
                    return;
                }
            }
            $probe->close();
        }

        // Gather candidate DB names
        $candidates = [];

        // 1) In-process globals (legacy)
        if (!empty($GLOBALS['db'])) {
            $candidates[] = (string)$GLOBALS['db'];
        }

        // 2) env vars
        if (!empty($_ENV['DB_NAME']))          $candidates[] = $_ENV['DB_NAME'];
        if (!empty($_ENV['MYSQL_DATABASE']))   $candidates[] = $_ENV['MYSQL_DATABASE'];

        // 3) server_config.inc.php array form
        $paths = [
            dirname(__DIR__) . '/data/server_config.inc.php',
            __DIR__ . '/../data/server_config.inc.php',
            '/data/server_config.inc.php',
        ];
        foreach ($paths as $p) {
            if (@is_file($p)) {
                $cfg = @include $p;
                if (is_array($cfg) && !empty($cfg['db'])) {
                    $candidates[] = (string)$cfg['db'];
                }
            }
        }

        // 4) server_config.inc.php constant form
        if (defined('EDTB_DB')) $candidates[] = (string)EDTB_DB;

        // 5) $_SERVER vars (container style)
        if (!empty($_SERVER['MYSQL_DATABASE'])) $candidates[] = (string)$_SERVER['MYSQL_DATABASE'];

        $candidates = array_values(array_unique(array_filter($candidates, static fn($v) => is_string($v) && $v !== '')));

        foreach ($candidates as $dbName) {
            if (@$this->mysqli->select_db($dbName)) {
                return;
            }
        }
    }

    private function safeQuery($query)
    {
        $result = $this->mysqli->query($query) or write_log($this->mysqli->error, __FILE__, __LINE__);
        return $result;
    }

    private function tableExists($table)
    {
        $query = "SHOW TABLES LIKE '" . $table . "'";
        $result = $this->mysqli->query($query);

        if ($result === false) {
            write_log($this->mysqli->error, __FILE__, __LINE__);
            return false;
        }

        $tableFound = $result->num_rows > 0;
        $result->close();

        return $tableFound;
    }
}

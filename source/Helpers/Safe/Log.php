<?php
/**
 * Write a log line (and a one-time env info file)
 *
 * @param string $msg
 * @param string $file
 * @param string|int $line
 * @param bool $debugOverride
 */
function write_log($msg, $file = '', $line = '', $debugOverride = false)
{
    global $settings, $systemTime;

    if ($debugOverride !== false || (isset($settings['debug']) && $settings['debug'] === 'true')) {
        // write user info file if not exists
        $lfile = $_SERVER['DOCUMENT_ROOT'] . '/edtb_log_info.txt';
        if (!file_exists($lfile)) {
            $ua = getBrowser();
            $debugInfo = 'Browser: ' . $ua['name'] . ' ' . $ua['version'] . ' (' . $ua['platform'] . ')' . PHP_EOL;
            $debugInfo .= 'Platform: ' . getOS() . PHP_EOL;
            $debugInfo .= 'Reported as: ' . $_SERVER['HTTP_USER_AGENT'] . PHP_EOL;
            $debugInfo .= 'HTTP_HOST: ' . $_SERVER['HTTP_HOST'] . PHP_EOL;
            $debugInfo .= 'SERVER_SOFTWARE: ' . $_SERVER['SERVER_SOFTWARE'] . PHP_EOL;
            $debugInfo .= 'SERVER_NAME: ' . $_SERVER['SERVER_NAME'] . PHP_EOL;
            $debugInfo .= 'SERVER_ADDR: ' . $_SERVER['SERVER_ADDR'] . PHP_EOL;

            file_put_contents($lfile, $debugInfo);
        }

        // this is the "edtb.log"
        $log = $_SERVER['DOCUMENT_ROOT'] . '/edtb.log';

        // add file and line info if available
        $where = ($file !== '' && $line !== '') ? '(<span title="' . $file . ' at line ' . $line . '">?</span>) ' : '';

        $fd = fopen($log, 'a');
        $str = '[' . date('d.m.Y H:i:s', time() + $systemTime * 60 * 60) . ']' . $where . $msg;

        fwrite($fd, $str . PHP_EOL);
        fclose($fd);
    }
}

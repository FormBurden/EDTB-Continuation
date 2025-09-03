#!/usr/bin/env bash
# dev-server.sh — color in CLI, quiet access in CLI, full logs to files
set -euo pipefail

cd "$(dirname "$0")/.."

LOG_DIR="logs"
mkdir -p "$LOG_DIR"

TS="$(date +"%Y%m%d-%H%M%S")"

ACCESS_LOG="$LOG_DIR/access-$TS.log"
SERVER_LOG="$LOG_DIR/server-$TS.log"
PHP_ERR_LOG="$LOG_DIR/php-$TS.log"

echo "[*] Logs:"
echo "    Access : $ACCESS_LOG"
echo "    Server : $SERVER_LOG"
echo "    PHP    : $PHP_ERR_LOG"
echo

# We give PHP a PTY via `script` so colors are allowed.
# We keep the UI clean (display_errors=0) but log to STDERR (php://stderr).
# Then:
#   - tee -> plain (no ANSI) copy to SERVER_LOG
#   - awk -> splits to ACCESS_LOG and PHP_ERR_LOG; prints only WARN/ERROR to console with colors

TERM="${TERM:-xterm-256color}" \
script -qefc "php \
  -d display_errors=0 \
  -d log_errors=1 \
  -d error_reporting=E_ALL \
  -d error_log=php://stderr \
  -S 127.0.0.1:8080 -t ." /dev/null \
| tee >(sed -r 's/\x1B\[[0-9;]*[mK]//g' >> "$SERVER_LOG") \
| awk -v access_log="$ACCESS_LOG" -v php_log="$PHP_ERR_LOG" '
  # ANSI helpers
  function c_reset() { return "\033[0m" }
  function c_red()   { return "\033[31;1m" }
  function c_yel()   { return "\033[33;1m" }
  function c_mag()   { return "\033[35;1m" }

  {
    line  = $0
    plain = line; gsub(/\x1B\[[0-9;]*[mK]/, "", plain)

    is_access   = (plain ~ /\[[0-9]{3}\]:/ || plain ~ /(Accepted|Closing)/)
    is_php_warn = (plain ~ /PHP (Warning|Deprecated)/)
    is_php_err  = (plain ~ /(PHP (Fatal error|Parse error|Recoverable fatal error))/)
    is_http4xx  = (plain ~ /\[[4][0-9][0-9]\]:/)
    is_http5xx  = (plain ~ /\[[5][0-9][0-9]\]:/)
    is_notice   = (plain ~ /PHP Notice/)

    # Always write access lines to access log (plain text)
    if (is_access) { print plain >> access_log; fflush(access_log) }

    # Always write PHP warnings/errors (including Notice) to php log (plain text)
    if (is_php_warn || is_php_err || is_notice) { print plain >> php_log; fflush(php_log) }

    # Console: show only WARN/ERROR (no access noise, no Notices).
    if (is_php_err)  { print c_red() line c_reset(); fflush() }
    else if (is_php_warn) { print c_yel() line c_reset(); fflush() }
    else if (is_http5xx)  { print c_red() line c_reset(); fflush() }
    else if (is_http4xx)  { print c_mag() line c_reset(); fflush() }
  }
'

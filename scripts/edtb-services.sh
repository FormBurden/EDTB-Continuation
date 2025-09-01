#!/usr/bin/env bash
# EDTB bulk systemd toggler: enable/disable & start/stop all listed units.

set -euo pipefail

# Resolve repo root from this script's location
SCRIPT_DIR="$(cd -- "$(dirname -- "${BASH_SOURCE[0]}")" && pwd)"
REPO_ROOT="$(cd -- "$SCRIPT_DIR/.." && pwd)"
UNITS_FILE="$REPO_ROOT/data/edtb-units.txt"

usage() {
  cat <<'EOF'
Usage:
  edtb-services.sh on [--user|--system]   Enable + start all units
  edtb-services.sh off [--user|--system]  Disable + stop all units
  edtb-services.sh status [--user|--system]
  edtb-services.sh reload [--user|--system]
  edtb-services.sh list

Notes:
  • --system (default) operates on system-wide units (needs sudo).
  • --user targets user units (~/.config/systemd/user). Consider 'loginctl enable-linger $USER' for boot autostart of user units.
  • Units to manage are read from data/edtb-units.txt (one per line, comments with #).
EOF
}

die() { echo "Error: $*" >&2; exit 1; }

# Parse action
ACTION="${1:-}"
[[ -z "$ACTION" || "$ACTION" == "-h" || "$ACTION" == "--help" ]] && { usage; exit 0; }

# Parse scope
SCOPE_FLAG="${2:-}"
MODE="system"
case "$SCOPE_FLAG" in
  ""|"--system") MODE="system" ;;
  "--user") MODE="user" ;;
  *) die "Unknown option: $SCOPE_FLAG" ;;
esac

# Scope arrays/privileges
if [[ "$MODE" == "user" ]]; then
  SCOPE=(--user)
  SUDO=""
else
  SCOPE=()
  if [[ ${EUID:-$(id -u)} -ne 0 ]]; then
    SUDO="sudo"
  else
    SUDO=""
  fi
fi

# Read unit list
[[ -f "$UNITS_FILE" ]] || die "Missing $UNITS_FILE. Create it and list your .service/.timer names."
mapfile -t UNITS < <(grep -v '^\s*#' "$UNITS_FILE" | sed '/^\s*$/d')

[[ ${#UNITS[@]} -gt 0 ]] || die "No units found in $UNITS_FILE."

run() {
  # Wrapper to show the command and run it
  echo "$*"
  eval "$@"
}

unit_exists() {
  # Return 0 if systemd knows this unit name
  if [[ "$MODE" == "user" ]]; then
    systemctl --user cat "$1" >/dev/null 2>&1 || systemctl --user status "$1" >/dev/null 2>&1
  else
    $SUDO systemctl cat "$1" >/dev/null 2>&1 || $SUDO systemctl status "$1" >/dev/null 2>&1
  fi
}

status_one() {
  local u="$1"
  local enabled="unknown"
  local active="unknown"

  if [[ "$MODE" == "user" ]]; then
    if systemctl --user is-enabled "$u" >/dev/null 2>&1; then enabled="enabled"; else enabled="disabled"; fi
    if systemctl --user is-active "$u"  >/dev/null 2>&1; then active="active";  else active="inactive"; fi
  else
    if $SUDO systemctl is-enabled "$u" >/dev/null 2>&1; then enabled="enabled"; else enabled="disabled"; fi
    if $SUDO systemctl is-active "$u"  >/dev/null 2>&1; then active="active";  else active="inactive"; fi
  fi

  printf "  %-40s  enabled=%-8s  active=%s\n" "$u" "$enabled" "$active"
}

action_on() {
  echo "Turning ON all EDTB units ($MODE scope):"
  if [[ "$MODE" == "user" ]]; then
    # Helpful hint for user services at boot
    if command -v loginctl >/dev/null 2>&1; then
      if [[ "$(loginctl show-user "$USER" -p Linger 2>/dev/null | cut -d= -f2)" != "yes" ]]; then
        echo "  Note: user linger is off; user units may not start at boot. To enable:  loginctl enable-linger \"$USER\""
      fi
    fi
  fi
  for u in "${UNITS[@]}"; do
    if ! unit_exists "$u"; then
      echo "  Skipping $u (systemd can't find this unit)"
      continue
    fi
    if [[ "$MODE" == "user" ]]; then
      run "systemctl --user enable --now \"$u\"" || true
    else
      run "$SUDO systemctl enable --now \"$u\"" || true
    fi
  done
}

action_off() {
  echo "Turning OFF all EDTB units ($MODE scope):"
  for u in "${UNITS[@]}"; do
    if ! unit_exists "$u"; then
      echo "  Skipping $u (systemd can't find this unit)"
      continue
    fi
    if [[ "$MODE" == "user" ]]; then
      # Stop first, then disable (cleaner messages)
      run "systemctl --user stop \"$u\"" || true
      run "systemctl --user disable \"$u\"" || true
    else
      run "$SUDO systemctl stop \"$u\"" || true
      run "$SUDO systemctl disable \"$u\"" || true
    fi
  done
}

action_status() {
  echo "Status for EDTB units ($MODE scope):"
  for u in "${UNITS[@]}"; do
    status_one "$u"
  done
}

action_reload() {
  echo "Reloading systemd daemon ($MODE scope)…"
  if [[ "$MODE" == "user" ]]; then
    run "systemctl --user daemon-reload"
  else
    run "$SUDO systemctl daemon-reload"
  fi
}

case "$ACTION" in
  on)     action_on ;;
  off)    action_off ;;
  status) action_status ;;
  reload) action_reload ;;
  list)
    echo "Units from $UNITS_FILE:"
    printf '  %s\n' "${UNITS[@]}"
    ;;
  *)
    usage; exit 1 ;;
esac

#!/usr/bin/env bash
set -euo pipefail

# Resolve repo root (this script lives in repo/scripts/)
REPO="$(cd "$(dirname "$0")/.." && pwd)"
GEN_DIR="$REPO/systemd-user"

UNITS=("edtb-edsm-import")

usage() {
  echo "Usage: $0 {enable-all|disable-all|start-all|stop-all|status|run-once}"
  exit 1
}

generate_units() {
  mkdir -p "$GEN_DIR"
  for u in "${UNITS[@]}"; do
    sed "s#__REPO__#${REPO}#g" "$REPO/${u}.service.in" > "$GEN_DIR/${u}.service"
    sed "s#__REPO__#${REPO}#g" "$REPO/${u}.timer.in"   > "$GEN_DIR/${u}.timer"
  done
}

link_units() {
  # Link the generated units into your user manager directly from the repo
  systemctl --user link "$GEN_DIR"/*.service "$GEN_DIR"/*.timer >/dev/null
}

enable_all() {
  generate_units
  link_units
  for u in "${UNITS[@]}"; do
    systemctl --user enable --now "${u}.timer"
  done
}

disable_all() {
  for u in "${UNITS[@]}"; do
    systemctl --user disable --now "${u}.timer" || true
    systemctl --user reset-failed "${u}.service" || true
  done
}

start_all() {
  for u in "${UNITS[@]}"; do
    systemctl --user start "${u}.service"
  done
}

stop_all() {
  for u in "${UNITS[@]}"; do
    systemctl --user stop "${u}.service" || true
  done
}

status_all() {
  for u in "${UNITS[@]}"; do
    echo "==> ${u}.timer"
    systemctl --user status "${u}.timer" --no-pager || true
    echo
    echo "==> ${u}.service (last run)"
    systemctl --user status "${u}.service" --no-pager || true
    echo
  done
}

run_once() {
  # Immediate manual run via the user service
  systemctl --user start "edtb-edsm-import.service"
}

[[ $# -ge 1 ]] || usage
case "$1" in
  enable-all)  enable_all ;;
  disable-all) disable_all ;;
  start-all)   start_all ;;
  stop-all)    stop_all ;;
  status)      status_all ;;
  run-once)    run_once ;;
  *) usage ;;
esac

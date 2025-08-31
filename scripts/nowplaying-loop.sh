#!/usr/bin/env bash
set -euo pipefail
OUT="data/nowplaying.txt"
mkdir -p "$(dirname "$OUT")"
while :; do
  if playerctl status >/dev/null 2>&1; then
    ARTIST="$(playerctl metadata artist 2>/dev/null || echo '')"
    TITLE="$(playerctl metadata title 2>/dev/null || echo '')"
    [[ -n "$TITLE$ARTIST" ]] && echo "$ARTIST - $TITLE" > "$OUT" || : 
  fi
  sleep 2
done

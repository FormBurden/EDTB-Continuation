#!/usr/bin/env bash
set -euo pipefail
SRC="$(php -r '$c=include "data/server_config.inc.php"; echo $c["screens_dir"]??"";')"
DST="data/gallery"
[[ -d "$SRC" ]] || { echo "Screens dir not found: $SRC"; exit 0; }
mkdir -p "$DST"
rsync -a --include='*.bmp' --include='*.png' --include='*.jpg' --exclude='*' "$SRC"/ "$DST"/
# convert BMPs to JPG, keep original (or delete after if you like)
find "$DST" -type f -iname '*.bmp' -print0 | while IFS= read -r -d '' f; do
  jpg="${f%.*}.jpg"
  [[ -f "$jpg" ]] || magick "$f" "$jpg"
done

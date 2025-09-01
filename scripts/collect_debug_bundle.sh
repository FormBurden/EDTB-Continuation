#!/usr/bin/env bash
set -euo pipefail

ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
OUT="$ROOT/edtb_debug_$(date +%Y%m%d_%H%M%S)"
mkdir -p "$OUT"

# 1) Key PHP source files
cp -a "$ROOT/src/Config.php" "$OUT/" 2>/dev/null || true
cp -a "$ROOT/bin/migrate" "$OUT/" 2>/dev/null || true
cp -a "$ROOT/source/config.inc.php" "$OUT/" 2>/dev/null || true
cp -a "$ROOT/source/config_ini.inc.php" "$OUT/" 2>/dev/null || true
cp -a "$ROOT/style/Header.php" "$OUT/" 2>/dev/null || true
cp -a "$ROOT/style/Theme.php" "$OUT/" 2>/dev/null || true
cp -a "$ROOT/NearestSystems/NearestSystems.php" "$OUT/" 2>/dev/null || true
cp -a "$ROOT/NearestSystems/index.php" "$OUT/" 2>/dev/null || true
cp -a "$ROOT/get/getData.php" "$OUT/" 2>/dev/null || true
cp -a "$ROOT/get/getData_leftColumn.php" "$OUT/" 2>/dev/null || true
cp -a "$ROOT/System/getData_systemInfo.php" "$OUT/" 2>/dev/null || true
cp -a "$ROOT/DataPoint/Vendor/MySQL_table_edit/mte.php" "$OUT/" 2>/dev/null || true

# 2) Composer deps snapshot
cp -a "$ROOT/composer.json" "$OUT/" 2>/dev/null || true
cp -a "$ROOT/composer.lock" "$OUT/" 2>/dev/null || true

# 3) DB schema only (no data) — requires edtb user/password
# If this fails, run the mysqldump manually and copy the file in.
if command -v mysqldump >/dev/null 2>&1; then
  echo "Dumping schema (you may be prompted for password)…"
  mysqldump -d -u edtb -p edtb > "$OUT/edtb_schema.sql" || true
fi

# 4) Environment snapshots
php -v  > "$OUT/php_version.txt"  2>&1 || true
php -m  > "$OUT/php_modules.txt"  2>&1 || true
mariadb --version > "$OUT/mariadb_version.txt" 2>&1 || true

# 5) Redacted server_config (if present)
if [ -f "$ROOT/data/server_config.inc.php" ]; then
  awk '
    BEGIN{FS="=>"}
    /password|token|secret/ {print $1 "=> REDACTED"; next}
    {print}
  ' "$ROOT/data/server_config.inc.php" > "$OUT/server_config.inc.redacted.php" || true
fi

TARBALL="${OUT}.tar.gz"
tar -czf "$TARBALL" -C "$OUT" .
echo "Bundle created: $TARBALL"
echo "Upload that tar.gz here."

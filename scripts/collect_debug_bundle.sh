#!/usr/bin/env bash
set -Eeuo pipefail

# Locate repo root from script location (expects to live in scripts/)
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
ROOT="$(cd "$SCRIPT_DIR/.." && pwd)"

timestamp="$(date +%Y%m%d_%H%M%S)"
BUNDLE_NAME="edtb_debug_${timestamp}"
INCLUDE_PATHS=()
NO_DEFAULTS=0
DRY_RUN=0
INCLUDE_FROM=""
READ_STDIN=0
EXCLUDE_LOGS=0

# Hostname redaction for outputs
HOST_RAW="$(hostname 2>/dev/null || uname -n || echo 'UNKNOWN_HOST')"
HOST_ESC="$(printf '%s' "$HOST_RAW" | sed 's/[.[*^$()+?{}|\/]/\\&/g')"
redact_host() { sed "s/${HOST_ESC}/<REDACTED_HOST>/g"; }

usage() {
  cat <<'USAGE'
Usage:
  scripts/collect_debug_bundle.sh [options] [paths...]

Options:
  --name NAME         Name the bundle (default: edtb_debug_YYYYmmdd_HHMMSS)
  --no-defaults       Only include the paths you pass (skip defaults)
  --from FILE         Read extra paths (newline-separated) from FILE
  --stdin             Also read extra paths from STDIN (newline-separated)
  --dry-run           Show contents, don't create the tar.gz
  --help              Show this help
USAGE
}

die(){ echo "Error: $*" >&2; exit 1; }
info(){ echo "[*] $*"; }

# Parse args
while [[ $# -gt 0 ]]; do
  case "$1" in
    --name) shift; [[ $# -gt 0 ]] || die "--name requires a value"; BUNDLE_NAME="$1";;
    --no-defaults) NO_DEFAULTS=1;;
    --from) shift; [[ $# -gt 0 ]] || die "--from requires a file"; INCLUDE_FROM="$1";;
    --stdin) READ_STDIN=1;;
    --dry-run) DRY_RUN=1;;
    --no-logs) EXCLUDE_LOGS=1;;
    --help) usage; exit 0;;
    --) shift; break;;
    -*) die "Unknown option: $1";;
    *) INCLUDE_PATHS+=("$1");;
  esac
  shift
done

# Positional extras
if [[ $# -gt 0 ]]; then
  while [[ $# -gt 0 ]]; do INCLUDE_PATHS+=("$1"); shift; done
fi

# Defaults to include unless user says --no-defaults
add_default_paths() {
  local -a defaults=(
    src/Config.php
    bin/migrate
    source/config.inc.php
    source/config_ini.inc.php
    style/Header.php
    style/Theme.php
    NearestSystems/NearestSystems.php
    NearestSystems/index.php
    get/getData.php
    System/getData_systemInfo.php
    action/saveSessionLog.php
    scripts/collect_debug_bundle.sh
    raw_links.txt
    composer.json
    composer.lock
    data/sessionlog.txt
  )
  local p
  for p in "${defaults[@]}"; do
    [[ -e "$ROOT/$p" ]] && INCLUDE_PATHS+=("$p")
  done
}

# Pull extras from file/stdin
if [[ -n "$INCLUDE_FROM" ]]; then
  [[ -f "$INCLUDE_FROM" ]] || die "Not found: $INCLUDE_FROM"
  while IFS= read -r line; do [[ -n "${line// }" ]] && INCLUDE_PATHS+=("$line"); done <"$INCLUDE_FROM"
fi
if [[ $READ_STDIN -eq 1 ]]; then
  while IFS= read -r line; do [[ -n "${line// }" ]] && INCLUDE_PATHS+=("$line"); done
fi
[[ $NO_DEFAULTS -eq 0 ]] && add_default_paths

# Bash-only dedupe
dedupe_in_place(){
  declare -A seen=(); local -a uniq=(); local x
  for x in "${INCLUDE_PATHS[@]}"; do
    if [[ -z "${seen[$x]+_}" ]]; then seen[$x]=1; uniq+=("$x"); fi
  done
  INCLUDE_PATHS=("${uniq[@]}")
}
dedupe_in_place

# Validate
MISSING=(); EXISTING=()
for rel in "${INCLUDE_PATHS[@]}"; do
  rel="${rel#./}"
  if [[ -e "$ROOT/$rel" ]]; then EXISTING+=("$rel"); else MISSING+=("$rel"); fi
done
if [[ ${#MISSING[@]} -gt 0 ]]; then
  echo "Warning: the following requested paths were not found:" >&2
  for m in "${MISSING[@]}"; do echo "  - $m" >&2; done
fi

# Pick a free bundle name if there is a conflict
pick_free_name(){
  local base="$1" n=0 try
  while :; do
    if [[ $n -eq 0 ]]; then try="$base"; else try="${base}-${n}"; fi
    if [[ ! -e "$ROOT/$try.tar.gz" && ! -d "$ROOT/$try" && ! -d "$ROOT/$try.tmp" ]]; then
      echo "$try"; return
    fi
    n=$((n+1))
  done
}
# Only auto-suffix when using the default auto name
if [[ "$BUNDLE_NAME" == edtb_debug_* ]]; then
  BUNDLE_NAME="$(pick_free_name "$BUNDLE_NAME")"
fi


OUT="$ROOT/$BUNDLE_NAME"
TMPDIR="$OUT.tmp"
mkdir -p "$TMPDIR"

# Structure snapshot (exclude vendor/node_modules/.git)
generate_structure(){
  {
    echo "# Repo structure snapshot (excluding vendor/ and node_modules/)"
    if command -v tree >/dev/null 2>&1; then
      (cd "$ROOT" && tree -a -I 'vendor|node_modules|.git' -L 4)
    else
      (cd "$ROOT" && find . -maxdepth 4 \
        -not -path './.git*' -not -path './vendor/*' -not -path './node_modules/*' \
        -printf '%p\n' | LC_ALL=C sort)
    fi
  } > "$TMPDIR/structure.txt"
}

# Env snapshot (hostname redacted)
generate_env(){
  {
    echo "# Environment snapshot"
    echo "date: $(date -Is)" | redact_host
    echo
    echo "uname -a:"; uname -a 2>/dev/null | redact_host || echo "(uname not available)"
    echo
    echo "php -v:"; php -v 2>/dev/null | redact_host || echo "(php not available)"
    echo
    echo "php -m:"; php -m 2>/dev/null | redact_host || true
    echo
    echo "mysql --version:"; mysql --version 2>/dev/null | redact_host || echo "(mysql client not available)"
    echo
    echo "git:"
    if (cd "$ROOT" && git rev-parse --is-inside-work-tree >/dev/null 2>&1); then
      { git -C "$ROOT" status --porcelain=v1; echo; git -C "$ROOT" log -1 --oneline; echo; git -C "$ROOT" rev-parse HEAD; } | redact_host
    else
      echo "(not a git repo)"
    fi
  } > "$TMPDIR/env.txt"
}

# Redacted configs
copy_redacted_configs(){
  if [[ -f "$ROOT/data/server_config.inc.php" ]]; then
    sed -E \
      's/(["'"'"'](password|token|secret|api|apikey|key)["'"'"']\s*=>\s*)(["'"'"']).*?\3/\1\3REDACTED\3/gI; s/(["'"'"']db_?pass(word)?["'"'"']\s*=>\s*)(["'"'"']).*?\3/\1\3REDACTED\3/gI' \
      "$ROOT/data/server_config.inc.php" | redact_host > "$TMPDIR/server_config.inc.redacted.php" || true

  fi
  if [[ -f "$ROOT/.env" ]]; then
    sed -E 's/(PASS|PASSWORD|TOKEN|SECRET|KEY|API[_A-Z]*)=.*/\1=REDACTED/g' \
      "$ROOT/.env" | redact_host > "$TMPDIR/.env.redacted" || true
  fi
}

# Copy requested files/dirs
copy_requested(){
  local base="$TMPDIR/requested"; mkdir -p "$base"; local rel
  for rel in "${EXISTING[@]}"; do
    if [[ -d "$ROOT/$rel" ]]; then
      mkdir -p "$base/$rel"
      if command -v rsync >/dev/null 2>&1; then
        rsync -a --delete --exclude '.git' --exclude 'vendor' --exclude 'node_modules' "$ROOT/$rel/" "$base/$rel/" || true
      else
        cp -a "$ROOT/$rel"/. "$base/$rel/" || true
      fi
    else
      mkdir -p "$(dirname "$base/$rel")"
      cp -a "$ROOT/$rel" "$base/$rel" || true
    fi
  done
}

write_readme(){
  cat > "$TMPDIR/README.txt" <<EOF
This bundle was generated by scripts/collect_debug_bundle.sh

Sections:
  env.txt                        - system/tool versions, minimal git info (hostname redacted)
  structure.txt                  - repo tree snapshot (vendor/node_modules skipped)
  server_config.inc.redacted.php - if present, secrets REDACTED
  .env.redacted                  - if present, secrets REDACTED
  requested/                     - any files/dirs you explicitly asked to include
EOF
}

info "Creating bundle: ${BUNDLE_NAME}.tar.gz"
generate_env
generate_structure
copy_redacted_configs
copy_requested
write_readme

if [[ $DRY_RUN -eq 1 ]]; then
  info "Dry-run complete. Would create: ${BUNDLE_NAME}.tar.gz containing:"
  (cd "$TMPDIR" && find . -type f | sed 's#^\./##' | LC_ALL=C sort)
  rm -rf "$TMPDIR"
  exit 0
fi

# Include only recent log folders (within ±2 minutes of now)
if (( EXCLUDE_LOGS == 0 )); then
logs_root="$ROOT/logs"
if [[ -d "$logs_root" ]]; then
  now_epoch="$(date +%s)"
  # Scan only first-level directories that look like YYYYMMDD-HHMMSS
  while IFS= read -r -d '' dir; do
    base="$(basename "$dir")"
    if [[ "$base" =~ ^[0-9]{8}-[0-9]{6}$ ]]; then
      y=${base:0:4}; mo=${base:4:2}; da=${base:6:2}
      hh=${base:9:2}; mm=${base:11:2}; ss=${base:13:2}
      # Convert folder timestamp to epoch
      dir_epoch="$(date -d "${y}-${mo}-${da} ${hh}:${mm}:${ss}" +%s 2>/dev/null || echo 0)"
      diff=$(( now_epoch - dir_epoch ))
      # absolute value
      if (( diff < 0 )); then diff=$(( -diff )); fi
      # Only include if within 120 seconds
      if (( diff <= 120 )); then
        mkdir -p "$TMPDIR/logs/$base"
        cp -a "$dir/." "$TMPDIR/logs/$base/"
      fi
    fi
  done < <(find "$logs_root" -mindepth 1 -maxdepth 1 -type d -print0)
fi


fi
TARBALL="$ROOT/${BUNDLE_NAME}.tar.gz"
tar -C "$TMPDIR" -czf "$TARBALL" .
rm -rf "$TMPDIR"

echo "Bundle created: ${TARBALL}" | redact_host
echo "Upload that tar.gz here."

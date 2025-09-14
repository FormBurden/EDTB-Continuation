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
NO_NETWORK=0
CAPTURE_SEC=""
PROBES_STDIN=0
PROBES_FILE=""
TMPDIR="${TMPDIR:-$(mktemp -d -t edtb_bundle_XXXXXX)}"



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
  --no-logs           Exclude logs directory from the bundle
  --no-network        Do not attach to Firefox DevTools; skip console/network/DOM/perf capture
  --capture-sec N     Override Firefox capture window (seconds), e.g. --capture-sec 10
  --probes-stdin      Read probe commands from STDIN and run them; output saved to curls.txt
  --probes-file FILE  Read probe commands from FILE and run them; output saved to curls.txt

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
    --no-network) NO_NETWORK=1;;
    --dry-run) DRY_RUN=1;;
    --no-logs) EXCLUDE_LOGS=1;;
    --help) usage; exit 0;;
    --probes-stdin) PROBES_STDIN=1;;
    --probes-file) shift; [[ $# -gt 0 ]] || die "--probes-file requires a path"; PROBES_FILE="$1";;
      --capture-sec)
        CAPTURE_SEC="$2"
        shift
        ;;
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
if (( READ_STDIN )); then
  while IFS= read -r __line || [[ -n "$__line" ]]; do
    [[ -z "$__line" || "$__line" =~ ^[[:space:]]*# ]] && continue
    expand_and_add "$__line"
  done
fi

[[ $NO_DEFAULTS -eq 0 ]] && add_default_paths
# Expand entries like "dir/*" into all files inside that folder (non-recursive)
expand_star_entries(){
  local -a out=()
  local rel dir f
  shopt -s nullglob
  for rel in "${INCLUDE_PATHS[@]}"; do
    # normalize leading ./ if present
    rel="${rel#./}"
    if [[ "$rel" == */'*' ]]; then
      dir="${rel%/*}"
      if [[ -d "$ROOT/$dir" ]]; then
        # include files in the directory, skip subdirectories
        for f in "$ROOT/$dir"/*; do
          [[ -f "$f" ]] || continue
          out+=( "${f#$ROOT/}" )
        done
      else
        # leave as-is; the normal validation will warn if not found
        out+=( "$rel" )
      fi
    else
      out+=( "$rel" )
    fi
  done
  shopt -u nullglob
  INCLUDE_PATHS=("${out[@]}")
}
# Expand any entry containing shell globs (e.g., "database/migrations/2025*.sql")
expand_wildcard_entries(){
  local -a out=()
  local rel
  local -a matches
  shopt -s nullglob
  for rel in "${INCLUDE_PATHS[@]}"; do
    # normalize leading ./ if present
    rel="${rel#./}"
    if [[ "$rel" == *[\*\?\[]* ]]; then
      # Safely expand against ROOT using compgen; keep files only
      mapfile -t matches < <(compgen -G "$ROOT/$rel")
      if (( ${#matches[@]} )); then
        local m
        for m in "${matches[@]}"; do
          [[ -f "$m" ]] || continue
          out+=( "${m#$ROOT/}" )
        done
      else
        # No matches: keep the literal for normal validation/warnings
        out+=( "$rel" )
      fi
    else
      out+=( "$rel" )
    fi
  done
  shopt -u nullglob
  INCLUDE_PATHS=("${out[@]}")
}

# Bash-only dedupe
dedupe_in_place(){
  declare -A seen=(); local -a uniq=(); local x
  for x in "${INCLUDE_PATHS[@]}"; do
    if [[ -z "${seen[$x]+_}" ]]; then seen[$x]=1; uniq+=("$x"); fi
  done
  INCLUDE_PATHS=("${uniq[@]}")
}
expand_wildcard_entries
expand_star_entries
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

if [[ "$NO_NETWORK" -ne 1 ]]; then
  [[ -n "${CAPTURE_SEC-}" ]] && export FF_CAPTURE_WINDOW_SEC="$CAPTURE_SEC"
  "$ROOT/.edtb-venv/bin/python3" "$ROOT/scripts/capture_ff_attach.py" "$TMPDIR/browser"
fi


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

# --- BEGIN probes-to-curls.txt ---
# If either --probes-file or --probes-stdin was provided, run the probes and
# capture combined stdout/stderr to $TMPDIR/curls.txt (redacting hostname).
if [[ -n "${PROBES_FILE:-}" || "${PROBES_STDIN:-0}" -eq 1 ]]; then
  PROBE_TMP="$TMPDIR/.probes.sh"
  if [[ -n "${PROBES_FILE:-}" ]]; then
    cp -a -- "$PROBES_FILE" "$PROBE_TMP"
  else
    # Read the heredoc passed after --probes-stdin into a temp script
    cat > "$PROBE_TMP"
  fi
  chmod +x "$PROBE_TMP" || true
  {
    echo "### curls.txt — probe run $(date -Iseconds)"
    echo "### working dir: $ROOT"
    echo
    set -x
    bash -Eeuo pipefail "$PROBE_TMP"
  } 2>&1 | redact_host > "$TMPDIR/curls.txt" || true
fi
# --- END probes-to-curls.txt ---
# --- BEGIN format curls.txt with URL headers and spacing ---
# If a raw curls.txt exists and we still have the probe script, rebuild curls.txt
# so that each probe prints its URL and is separated by a blank line.
if [[ -f "$TMPDIR/curls.txt" && -f "${PROBE_TMP:-}" ]]; then
  CURLS_FMT="$TMPDIR/curls.formatted.txt"
  {
    echo "### curls.txt — probe run $(date -Iseconds)"
    echo "### working dir: $ROOT"
    echo
  } > "$CURLS_FMT"

  # Read probes line-by-line and execute them; print URL header for curl lines.
  # Comments and blank lines are skipped.
  while IFS= read -r _probe_line || [[ -n "$_probe_line" ]]; do
  # Skip comments/blank
  [[ -z "$_probe_line" ]] && continue
  [[ "$_probe_line" =~ ^[[:space:]]*# ]] && continue

  # Extract the first URL token if present (avoid complex [[ =~ ]] quoting)
  _url_header="$(printf '%s\n' "$_probe_line" | grep -oE 'https?://[^[:space:]]+' | head -n1 || true)"
  # Trim common trailing punctuation/quotes if they got pulled in
  _url_header="${_url_header%\"}"
  _url_header="${_url_header%\'}"
  _url_header="${_url_header%)}"
  _url_header="${_url_header%;}"

  if [[ "$_probe_line" == *curl* ]]; then
    printf '## CMD: %s\n' "$_probe_line" >> "$CURLS_FMT"
    printf '## URL: %s\n' "${_url_header:-<unknown>}" >> "$CURLS_FMT"
  fi


  # Execute the line and append output
  bash -Eeuo pipefail -c "$_probe_line" 2>&1 | redact_host >> "$CURLS_FMT" || true

  # Ensure a blank line between probes
  echo >> "$CURLS_FMT"
done < "$PROBE_TMP"


  # Replace original curls.txt
  mv -f "$CURLS_FMT" "$TMPDIR/curls.txt"
fi
# --- END format curls.txt with URL headers and spacing ---
#

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
  ref_epoch="$(date -d "${timestamp:0:4}-${timestamp:4:2}-${timestamp:6:2} ${timestamp:9:2}:${timestamp:11:2}:${timestamp:13:2}" +%s 2>/dev/null || date +%s)"
  # Scan only first-level directories that look like YYYYMMDD-HHMMSS
  while IFS= read -r -d '' dir; do
    base="$(basename "$dir")"
    if [[ "$base" =~ ^[0-9]{8}-[0-9]{6} ]]; then
      y=${base:0:4}; mo=${base:4:2}; da=${base:6:2}
      hh=${base:9:2}; mm=${base:11:2}; ss=${base:13:2}
      # Convert folder timestamp to epoch
      dir_epoch="$(date -d "${y}-${mo}-${da} ${hh}:${mm}:${ss}" +%s 2>/dev/null || echo 0)"
      diff=$(( ref_epoch - dir_epoch ))
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
# Build a checksums file inside the bundle root
CHECKSUM_FILE="$TMPDIR/CHECKSUMS.txt"
{
  echo "### EDTB Debug Bundle Checksums"
  echo "### Bundle Name: $BUNDLE_NAME"
  echo "### Created UTC: $(date -u '+%Y-%m-%dT%H:%M:%SZ')"
  echo

  # Requested files from --from .edtb-files.txt (copied under requested/)
  if [[ -d "$TMPDIR/requested" ]]; then
    (
      cd "$TMPDIR/requested"
      find . -type f -print0 | LC_ALL=C sort -z | \
      while IFS= read -r -d '' f; do
        printf '### %s\n' "requested/${f#./}"
        sha256sum "$f"
      done
    )
  fi

  # Logs (if present and not excluded)
  if [[ -d "$TMPDIR/logs" ]]; then
    (
      cd "$TMPDIR/logs"
      find . -type f -print0 | LC_ALL=C sort -z | \
      while IFS= read -r -d '' f; do
        printf '### %s\n' "logs/${f#./}"
        sha256sum "$f"
      done
    )
  fi

  # Firefox debug capture (browser/)
  if [[ -d "$TMPDIR/browser" ]]; then
    (
      cd "$TMPDIR/browser"
      find . -type f -print0 | LC_ALL=C sort -z | \
      while IFS= read -r -d '' f; do
        printf '### %s\n' "browser/${f#./}"
        sha256sum "$f"
      done
    )
  fi
} > "$CHECKSUM_FILE"

TARBALL="$ROOT/${BUNDLE_NAME}.tar.gz"
tar -C "$TMPDIR" -czf "$TARBALL" .
rm -rf "$TMPDIR"
TARBALL_SHA256="$(sha256sum "$TARBALL" | awk '{print $1}')"

# --- BEGIN .edtb-scope.txt append block ---
SCOPE_FILE="$ROOT/.edtb-scope.txt"

# Current branch (fallback to 'unknown' if git not available)
BRANCH="$(git -C "$ROOT" rev-parse --abbrev-ref HEAD 2>/dev/null || echo 'unknown')"

# Helper: get the last occurrence of a key prefix (e.g., "commit:", "mode:", "notes:")
_extract_last_line() {
  local key="$1"
  # Prints the last line in the file that starts with the key; empty if not found
  awk -v k="$key" 'index($0,k)==1{v=$0} END{if (v!="") print v}' "$SCOPE_FILE" 2>/dev/null || true
}

# Carry-forward values if they exist; otherwise set sensible defaults
COMMIT_LINE="$(_extract_last_line 'commit:')"
MODE_LINE="$(_extract_last_line 'mode:')"
NOTES_LINE="$(_extract_last_line 'notes:')"

if [[ -z "${COMMIT_LINE}" ]]; then
  # Default to the current repo commit (short) on first use
  COMMIT_LINE="commit: $(git -C "$ROOT" rev-parse --short HEAD 2>/dev/null || echo 'UNKNOWN')"
fi
if [[ -z "${MODE_LINE}" ]]; then
  MODE_LINE="mode: BUGFIX"
fi
if [[ -z "${NOTES_LINE}" ]]; then
  NOTES_LINE="notes: | Reference checksum for correct bundle and files."
fi

# Append a new block to .edtb-scope.txt
{
  echo "bundle: $(basename "$TARBALL")"
  echo "bundle checksum: ${TARBALL_SHA256}"
  echo "branch: ${BRANCH}"
  echo "${COMMIT_LINE}"
  echo "${MODE_LINE}"
  echo "SOURCES: files_only"
  echo "db_migrations: none"
  echo "${NOTES_LINE}"
  echo
} >> "$SCOPE_FILE"
# --- END .edtb-scope.txt append block ---

# --- BEGIN normalize last scope block missing files placement ---
# Rebuild ONLY the last scope block (from last 'bundle:' to EOF) so that:
#  - any existing 'missing files:' sections in that block are removed
#  - the current MISSING list is inserted exactly once immediately before 'notes:'
if [[ -f "${SCOPE_FILE:-}" && ${#MISSING[@]} -gt 0 ]]; then
  __last_bundle_line="$(grep -n '^bundle:' "$SCOPE_FILE" | tail -n1 | cut -d: -f1 || true)"
  if [[ -n "$__last_bundle_line" ]]; then
    __head_tmp="${SCOPE_FILE}.head.$$"
    __tail_tmp="${SCOPE_FILE}.tail.$$"
    __rebuilt="${SCOPE_FILE}.rebuilt.$$"

    # Split into head (everything before last bundle) and tail (last block)
    awk -v start="$__last_bundle_line" 'NR < start { print }' "$SCOPE_FILE" > "$__head_tmp"
    awk -v start="$__last_bundle_line" 'NR >= start { print }' "$SCOPE_FILE" > "$__tail_tmp"

    __skip_bullets=0
    __inserted=0
    : > "$__rebuilt"

    while IFS= read -r __line || [[ -n "$__line" ]]; do
      # If we're skipping bullet lines that belong to a prior 'missing files:' section
      if [[ $__skip_bullets -eq 1 ]]; then
        if [[ "$__line" == "  - "* ]]; then
          continue
        else
          __skip_bullets=0
          # fall through to process the first non-bullet after the section
        fi
      fi

      # Eat any existing 'missing files:' section(s) in the tail
      if [[ "$__line" == "missing files:" ]]; then
        __skip_bullets=1
        continue
      fi

      # Right before 'notes:', inject our clean 'missing files:' section (once)
      if [[ $__inserted -eq 0 && "$__line" == notes:\ * ]]; then
        echo "missing files:" >> "$__rebuilt"
        for __m in "${MISSING[@]}"; do
          [[ -z "$__m" || "$__m" =~ ^[[:space:]]*# ]] && continue
          echo "  - $__m" >> "$__rebuilt"
        done
        __inserted=1
      fi

      echo "$__line" >> "$__rebuilt"
    done < "$__tail_tmp"

    # Stitch head + rebuilt tail back together
    cat "$__head_tmp" "$__rebuilt" > "${SCOPE_FILE}.new"
    mv -f "${SCOPE_FILE}.new" "$SCOPE_FILE"
    rm -f "$__head_tmp" "$__tail_tmp" "$__rebuilt"
  fi
fi
# --- END normalize last scope block missing files placement ---


echo "Bundle created: ${TARBALL}" | redact_host
echo "SHA256: ${TARBALL_SHA256}"
echo "Upload that tar.gz here."

#!/bin/bash
set -euo pipefail

LABHUB_ROOT="$(cd "$(dirname "$0")/../.." && pwd)"
ASSETS_DIR="$LABHUB_ROOT/assets"
cd "$LABHUB_ROOT"

export PATH="/opt/homebrew/bin:/usr/local/bin:/usr/bin:/bin:/usr/sbin:/sbin"
PHP_BIN="$(command -v php || true)"

if [[ -z "$PHP_BIN" ]]; then
  echo "ERRO: php não encontrado no PATH." >&2
  exit 1
fi

SYNC_MODE=0
if [[ "${1:-}" == "--sync" ]]; then
  SYNC_MODE=1
fi

run_discover() {
  "$PHP_BIN" -d display_errors=0 -d error_reporting=24575 "$ASSETS_DIR/php/discover.php"
}

if [[ "$SYNC_MODE" -eq 1 ]]; then
  run_discover
  exit $?
fi

LOG_FILE="$ASSETS_DIR/data/discover.log"
{
  echo "----- $(date -Iseconds) -----"
  run_discover
} >>"$LOG_FILE" 2>&1

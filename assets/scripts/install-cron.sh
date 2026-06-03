#!/bin/bash
set -euo pipefail

LABHUB_ROOT="$(cd "$(dirname "$0")/../.." && pwd)"
DISCOVER_SCRIPT="$LABHUB_ROOT/assets/scripts/discover-hosts.sh"
CRON_LINE="*/5 * * * * $DISCOVER_SCRIPT"

chmod +x "$DISCOVER_SCRIPT"

EXISTING="$(crontab -l 2>/dev/null || true)"
if printf '%s\n' "$EXISTING" | grep -Fq "$DISCOVER_SCRIPT"; then
  echo "Cron já instalado:"
  printf '%s\n' "$EXISTING" | grep "$DISCOVER_SCRIPT"
  exit 0
fi

if printf '%s\n' "$EXISTING" | grep -Fq "$LABHUB_ROOT/discover-hosts.sh"; then
  echo "Remova a entrada antiga do cron (discover-hosts.sh na raiz) e execute de novo."
  exit 1
fi

{
  printf '%s\n' "$EXISTING" | sed '/^$/d'
  echo "$CRON_LINE"
} | crontab -

echo "Cron instalado (a cada 5 minutos):"
echo "$CRON_LINE"

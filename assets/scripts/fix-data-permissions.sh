#!/bin/bash
# Corrige permissões de assets/data para o Lab Hub gravar hosts.json (Linux).
# Uso: ./assets/scripts/fix-data-permissions.sh
#      sudo ./assets/scripts/fix-data-permissions.sh   (recomendado)

set -euo pipefail

LABHUB_ROOT="$(cd "$(dirname "$0")/../.." && pwd)"
DATA_DIR="$LABHUB_ROOT/assets/data"
HOSTS_FILE="$DATA_DIR/hosts.json"

run_as_root() {
  if [[ "$(id -u)" -eq 0 ]]; then
    "$@"
  elif command -v sudo >/dev/null 2>&1; then
    sudo "$@"
  else
    echo "ERRO: precisa de root ou sudo para ajustar dono/permissões." >&2
    echo "Execute: sudo $0" >&2
    exit 1
  fi
}

detect_web_user() {
  if [[ -n "${WEB_USER:-}" ]]; then
    echo "$WEB_USER"
    return
  fi

  local u
  for u in www-data apache nginx http; do
    if id "$u" >/dev/null 2>&1; then
      if pgrep -u "$u" -x 'apache2|httpd|nginx|php-fpm' >/dev/null 2>&1 \
        || pgrep -u "$u" >/dev/null 2>&1; then
        echo "$u"
        return
      fi
    fi
  done

  for u in www-data apache nginx http; do
    if id "$u" >/dev/null 2>&1; then
      echo "$u"
      return
    fi
  done

  echo ""
}

WEB_USER="$(detect_web_user)"
if [[ -z "$WEB_USER" ]]; then
  echo "ERRO: não encontrei usuário do servidor web (www-data, apache, nginx)." >&2
  echo "Defina manualmente: WEB_USER=www-data sudo $0" >&2
  exit 1
fi

echo "Lab Hub: $LABHUB_ROOT"
echo "Pasta de dados: $DATA_DIR"
echo "Usuário do servidor web: $WEB_USER"
echo ""

run_as_root mkdir -p "$DATA_DIR"

if [[ -f "$HOSTS_FILE" ]]; then
  run_as_root chown "$WEB_USER:$WEB_USER" "$HOSTS_FILE"
  run_as_root chmod 664 "$HOSTS_FILE"
fi

run_as_root chown "$WEB_USER:$WEB_USER" "$DATA_DIR"
run_as_root chmod 775 "$DATA_DIR"

# Grupo do deployer pode escrever também (opcional)
DEPLOY_GROUP="$(id -gn)"
if [[ -n "$DEPLOY_GROUP" ]] && getent group "$DEPLOY_GROUP" >/dev/null 2>&1; then
  run_as_root chgrp "$DEPLOY_GROUP" "$DATA_DIR" 2>/dev/null || true
  if [[ -f "$HOSTS_FILE" ]]; then
    run_as_root chgrp "$DEPLOY_GROUP" "$HOSTS_FILE" 2>/dev/null || true
  fi
fi

echo "Testando escrita como $WEB_USER..."
if run_as_root -u "$WEB_USER" touch "$DATA_DIR/.write-test"; then
  run_as_root -u "$WEB_USER" rm -f "$DATA_DIR/.write-test"
  echo "OK: $WEB_USER pode gravar em $DATA_DIR"
else
  echo "ERRO: $WEB_USER não conseguiu gravar em $DATA_DIR" >&2
  exit 1
fi

if command -v getenforce >/dev/null 2>&1 && [[ "$(getenforce 2>/dev/null)" == "Enforcing" ]]; then
  if command -v chcon >/dev/null 2>&1; then
    echo ""
    echo "SELinux ativo — aplicando contexto de escrita HTTP..."
    run_as_root chcon -R -t httpd_sys_rw_content_t "$DATA_DIR" 2>/dev/null \
      || echo "AVISO: chcon falhou; talvez precise: semanage fcontext -a -t httpd_sys_rw_content_t \"${DATA_DIR}(/.*)?\""
  fi
fi

echo ""
echo "Pronto. Rode a varredura:"
echo "  php $LABHUB_ROOT/assets/php/discover.php"
echo "ou use o botão no hub no navegador."

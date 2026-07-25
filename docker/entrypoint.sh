#!/bin/sh
# entrypoint.sh — ponte de configuração para o OpenLiteSpeed/LSPHP.
#
# O LiteSpeed roda o PHP (LSPHP) como processo separado e NÃO repassa as
# variáveis de ambiente do contêiner para o PHP. Como o EasyPanel injeta as
# credenciais como variáveis de ambiente, aqui, na subida do contêiner, elas
# são gravadas num arquivo .env que o PHP lê (fora da pasta pública).
set -e

ENV_OUT="/var/www/vhosts/localhost/.env"   # fora de html/ — não acessível pela web

# Grava apenas as chaves que interessam e que estejam definidas.
: > "$ENV_OUT"
for VAR in API_TIPO \
           API_DIRECTUS_CONFIGURACOES TOKEN_DIRECTUS_CONFIGURACOES \
           DIRECTUS_URL DIRECTUS_TOKEN \
           DIRECTUS_STORAGE TOKEN_PURGA_SITE \
           TOKEN_MATRICULA_EXTERNA AVASET_MATRICULA_URL; do
  eval "VAL=\${$VAR:-}"
  if [ -n "$VAL" ]; then
    printf '%s=%s\n' "$VAR" "$VAL" >> "$ENV_OUT"
  fi
done

# O PHP roda como lsadm; garante leitura sem expor além do necessário.
chown lsadm:lsadm "$ENV_OUT" 2>/dev/null || true
chmod 640 "$ENV_OUT" 2>/dev/null || true

# Entrega o controle ao início original da imagem oficial do OpenLiteSpeed.
if [ -x /entrypoint.sh ]; then
  exec /entrypoint.sh "$@"
elif [ -x /usr/local/bin/entrypoint.sh ]; then
  exec /usr/local/bin/entrypoint.sh "$@"
else
  # Fallback: sobe o OpenLiteSpeed em foreground.
  /usr/local/lsws/bin/lswsctrl start
  exec tail -f /usr/local/lsws/logs/error.log
fi

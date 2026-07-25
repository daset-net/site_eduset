# ============================================================
#  EDUSET — Site institucional
#  Servidor: OpenLiteSpeed + LSPHP (super rápido)
#  Imagem oficial: litespeedtech/openlitespeed
# ============================================================
FROM litespeedtech/openlitespeed:latest

LABEL org.opencontainers.image.title="EDUSET" \
      org.opencontainers.image.description="Site institucional EDUSET sobre OpenLiteSpeed + PHP" \
      maintainer="daset-net"

ENV TZ=America/Sao_Paulo \
    DEBIAN_FRONTEND=noninteractive

# Docroot padrão do vhost "localhost" na imagem oficial
ENV DOCROOT=/var/www/vhosts/localhost/html

# ---- Aplicação -------------------------------------------------------------
# Limpa o conteúdo de exemplo e copia o site
RUN rm -rf ${DOCROOT} && mkdir -p ${DOCROOT}
COPY public/ ${DOCROOT}/

# Pasta de dados persistente (leads do formulário)
RUN mkdir -p /var/www/vhosts/localhost/data \
    && chown -R lsadm:lsadm /var/www/vhosts/localhost \
    && chmod -R 775 /var/www/vhosts/localhost/data

# ---- Ponte de configuração -------------------------------------------------
# Grava as variáveis de ambiente (injetadas pelo EasyPanel) num .env que o PHP
# lê, contornando o fato de o LiteSpeed não repassar env para o LSPHP.
COPY docker/entrypoint.sh /usr/local/bin/eduset-entrypoint.sh
RUN sed -i 's/\r$//' /usr/local/bin/eduset-entrypoint.sh \
    && chmod +x /usr/local/bin/eduset-entrypoint.sh

# ---- Rede ------------------------------------------------------------------
# 80  -> HTTP do site      | 7080 -> painel admin do OpenLiteSpeed
EXPOSE 80 7080

# Nosso entrypoint prepara o .env e entrega o controle ao início oficial da imagem.
ENTRYPOINT ["/usr/local/bin/eduset-entrypoint.sh"]

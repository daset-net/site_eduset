FROM litespeedtech/openlitespeed:latest

# Remove os arquivos padrão de exemplo do OpenLiteSpeed
RUN rm -rf /usr/local/lsws/Example/html/*

# Copia os arquivos do nosso site para o diretório padrão
COPY index.html /usr/local/lsws/Example/html/
COPY style.css /usr/local/lsws/Example/html/
COPY main.js /usr/local/lsws/Example/html/

# Expõe as portas padrão
# 80 e 443: HTTP/HTTPS
# 7080: Painel de Controle Admin
# 8088: Porta de exemplo padrão do OLS
EXPOSE 80 443 7080 8088

FROM litespeedtech/openlitespeed:latest

# Define o diretório de trabalho padrão do OpenLiteSpeed no Docker
WORKDIR /var/www/vhosts/localhost/html

# Remove os arquivos padrão de exemplo do OpenLiteSpeed
RUN rm -rf /var/www/vhosts/localhost/html/*

# Copia os arquivos do nosso site para o diretório padrão
COPY index.html ./
COPY style.css ./
COPY main.js ./
COPY exemplo.html ./

# Expõe as portas padrão
EXPOSE 80 443 7080 8088

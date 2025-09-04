# ---- Base image ----
FROM php:8.3-apache

# Enable required Apache modules
RUN a2enmod rewrite headers proxy proxy_http

# (Optional) If you need PHP extensions, install them here
RUN docker-php-ext-install pdo pdo_mysql
RUN a2enmod ssl

# ---- App files ----
WORKDIR /var/www/html
COPY . /var/www/html

# ---- VirtualHost: proxy /hls/ and set CORS only for that path ----
RUN set -eux; \
  cat >/etc/apache2/sites-available/steps-proxy.conf <<'APACHECONF'
<VirtualHost *:80>
    ServerName localhost
    DocumentRoot /var/www/html



    ProxyPreserveHost off
    SSLProxyEngine On

   ProxyPass        /hls/  https://hls-proxy-iphq.onrender.com/ retry=0
   ProxyPassReverse /hls/  https://hls-proxy-iphq.onrender.com/hls/
   
    <Location /hls/>
        Header always set Access-Control-Allow-Origin "*"
        Header always set Access-Control-Allow-Headers "Range, Origin, Accept, Us"
        Header always set Access-Control-Expose-Headers "Content-Length, Content-Range"
        Header always set Access-Control-Allow-Methods "GET, HEAD, OPTIONS"
    </Location>

    <Directory /var/www/html>
        Options -Indexes
        AllowOverride All
        Require all granted
    </Directory>
</VirtualHost>
APACHECONF

# Activate site and disable default
RUN a2dissite 000-default && a2ensite steps-proxy

# ---- Make Apache honor $PORT at runtime (Render) ----
RUN set -eux; \
  mkdir -p /docker-entrypoint-initapache2.d; \
  cat >/docker-entrypoint-initapache2.d/01-render-port.sh <<'SH'
#!/bin/sh
set -eu
if [ -n "${PORT:-}" ]; then
  sed -ri "s/Listen 80/Listen ${PORT}/" /etc/apache2/ports.conf
  sed -ri "s#<VirtualHost \*:80>#<VirtualHost *:${PORT}>#" /etc/apache2/sites-available/steps-proxy.conf
fi
SH
RUN chmod +x /docker-entrypoint-initapache2.d/01-render-port.sh

# Expose default port (Render may override with $PORT)
EXPOSE 80

# Start Apache
CMD ["apache2-foreground"]

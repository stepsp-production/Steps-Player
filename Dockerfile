FROM php:8.3-apache

# Enable modules
RUN a2enmod rewrite headers proxy proxy_http

# App files
WORKDIR /var/www/html
COPY . /var/www/html

# Write a vhost that proxies /hls/ and adds CORS
RUN set -eux; \
  cat >/etc/apache2/sites-available/steps-proxy.conf <<'APACHECONF'
<VirtualHost *:80>
    ServerName localhost
    DocumentRoot /var/www/html

    ProxyPreserveHost On

    # Change upstream if needed
    ProxyPass        /hls/  http://stream.hls-proxy-iphq.onrender.com/hls/ retry=0
    ProxyPassReverse /hls/  http://stream.hls-proxy-iphq.onrender.com/hls/

    <Location /hls/>
        Header always set Access-Control-Allow-Origin "*"
        Header always set Access-Control-Allow-Headers "Range, Origin, Accept, User-Agent"
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

# Activate the site (separate RUN so Docker doesn't parse 'a2dissite' as an instruction)
RUN a2dissite 000-default && a2ensite steps-proxy

# Optional: honor $PORT on platforms like Render
RUN set -eux; \
  printf '%s\n' \
    'if [ -n "$PORT" ]; then' \
    '  sed -ri "s/Listen 80/Listen ${PORT}/" /etc/apache2/ports.conf;' \
    '  sed -ri "s#<VirtualHost \*:80>#<VirtualHost *:${PORT}>#" /etc/apache2/sites-available/steps-proxy.conf;' \
    'fi' \
  > /docker-entrypoint-initapache2.d/01-render-port.sh && \
  chmod +x /docker-entrypoint-initapache2.d/01-render-port.sh

EXPOSE 80
CMD ["apache2-foreground"]

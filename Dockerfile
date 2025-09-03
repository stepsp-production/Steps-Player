FROM php:8.3-apache

# Enable needed modules
RUN a2enmod rewrite headers proxy proxy_http

# Workdir + app files
WORKDIR /var/www/html
COPY . /var/www/html

# Add a vhost that proxies /hls/ to your HLS origin and sets CORS
# Also make Apache listen on $PORT if provided (Render)
RUN set -eux; \
  cat >/etc/apache2/sites-available/steps-proxy.conf <<'APACHECONF'
<VirtualHost *:80>
    ServerName _
    DocumentRoot /var/www/html

    ProxyPreserveHost On
    # Upstream HLS origin (change if needed)
    ProxyPass        /hls/  http://stream.hls-proxy-iphq.onrender.com/hls/ retry=0
    ProxyPassReverse /hls/  http://stream.hls-proxy-iphq.onrender.com/hls/

    # Limit CORS to proxied path
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
  a2dissite 000-default && a2ensite steps-proxy

# Make Apache honor $PORT at runtime (Render sets this)
# If $PORT is unset, it will continue to use 80.
RUN set -eux; \
  echo 'if [ -n "$PORT" ]; then \
          sed -ri "s/Listen 80/Listen ${PORT}/" /etc/apache2/ports.conf; \
          sed -ri "s#<VirtualHost \\*:80>#<VirtualHost *:${PORT}>#" /etc/apache2/sites-available/steps-proxy.conf; \
        fi' > /docker-entrypoint-initapache2.d/01-render-port.sh && \
  chmod +x /docker-entrypoint-initapache2.d/01-render-port.sh

EXPOSE 80
CMD ["apache2-foreground"]

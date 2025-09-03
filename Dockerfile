FROM php:8.3-apache

# فعّل وحدات البروكسي + الهيدرز
RUN a2enmod rewrite headers proxy proxy_http

# انسخ الموقع والكونفِغ
WORKDIR /var/www/html
COPY . /var/www/html

# أضف إعداد موقع يمرّر /hls/ إلى سيرفر البث (بدّل YOUR_ORIGIN)
RUN set -eux; \
  echo '<VirtualHost *:80>
    ServerName _
    DocumentRoot /var/www/html

    ProxyPreserveHost On
    # بدّل http://YOUR_ORIGIN إلى عنوان سيرفر البث الحقيقي (مثلاً http://stream.yourdomain.com)
    ProxyPass        /hls/  https://46.152.153.249/hls/
    ProxyPassReverse /hls/  https://hls-proxy-iphq.onrender.com/hls/

    # CORS أساسي لملفات HLS
    Header always set Access-Control-Allow-Origin "*"
    Header always set Access-Control-Allow-Headers "Range, Origin, Accept, User-Agent"
    Header always set Access-Control-Expose-Headers "Content-Length, Content-Range"
    Header always set Access-Control-Allow-Methods "GET, HEAD, OPTIONS"

    <Directory /var/www/html>
      Options -Indexes
      AllowOverride All
      Require all granted
    </Directory>
  </VirtualHost>' > /etc/apache2/sites-available/steps-proxy.conf && \
  a2ensite steps-proxy && a2dissite 000-default

EXPOSE 80
CMD ["apache2-foreground"]


CMD ["apache2-foreground"]

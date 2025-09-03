# Dockerfile
FROM php:8.3-apache

RUN a2enmod rewrite headers

WORKDIR /var/www/html
COPY . /var/www/html

EXPOSE 80

CMD ["apache2-foreground"]

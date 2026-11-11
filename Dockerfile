FROM php:8.2-apache
RUN apt-get update && apt-get install -y --no-install-recommends libpng-dev libjpeg-dev libfreetype6-dev libzip-dev ca-certificates \
 && docker-php-ext-configure gd --with-freetype --with-jpeg \
 && docker-php-ext-install -j$(nproc) pdo_mysql mysqli gd mbstring curl zip \
 && a2enmod rewrite headers \
 && rm -rf /var/lib/apt/lists/*
COPY . /var/www/html/
RUN chown -R www-data:www-data /var/www/html/uploads /var/www/html/storage \
 && chmod -R 775 /var/www/html/uploads /var/www/html/storage \
 && printf '#!/bin/sh\nset -e\nPORT=${PORT:-80}\nsed -i "s/Listen 80/Listen $PORT/" /etc/apache2/ports.conf\nsed -i "s/:80>/:$PORT>/" /etc/apache2/sites-enabled/000-default.conf\nexec apache2-foreground\n' > /usr/local/bin/start.sh \
 && chmod +x /usr/local/bin/start.sh
EXPOSE 80
CMD ["/usr/local/bin/start.sh"]

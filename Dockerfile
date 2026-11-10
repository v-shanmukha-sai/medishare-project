FROM php:8.2-apache

# PostgreSQL PDO driver + opcache. curl, mbstring, fileinfo and openssl are
# already compiled into the official PHP image.
RUN apt-get update \
    && apt-get install -y --no-install-recommends libpq-dev \
    && docker-php-ext-install -j"$(nproc)" pdo_pgsql pgsql opcache \
    && a2enmod rewrite headers \
    && rm -rf /var/lib/apt/lists/*

# Fail the build if a required extension is missing.
RUN php -m | grep -qi '^pdo_pgsql$' \
    && php -m | grep -qi '^pgsql$' \
    && php -m | grep -qi '^curl$' \
    && php -m | grep -qi '^mbstring$' \
    && php -m | grep -qi '^fileinfo$' \
    && php -m | grep -qi '^openssl$'

COPY docker/php.ini /usr/local/etc/php/conf.d/medishare.ini
COPY docker/apache-security.conf /etc/apache2/conf-enabled/zz-medishare.conf
COPY docker/entrypoint.sh /usr/local/bin/medishare-entrypoint
RUN chmod +x /usr/local/bin/medishare-entrypoint

WORKDIR /var/www/html
COPY . /var/www/html

RUN mkdir -p uploads/licenses uploads/medicines logs \
    && chown -R www-data:www-data uploads logs

EXPOSE 80
ENTRYPOINT ["medishare-entrypoint"]

############################
# image with
# Joomla 3.8 + VirtueMart 3.2 (offline installer bundle taken from the
# unmaintained opentools/docker-virtuemart:j3vm3 image, which is frozen on
# an end-of-life Debian 9 "stretch" base whose apt repos no longer resolve)
# PHP 7.3 + Apache (Joomla 3.8.x predates official PHP 7.4 support)
# Composer 2.x
# Node 22.x + pnpm
# mariadb-client
############################
FROM opentools/docker-virtuemart:j3vm3 AS vmsrc

FROM php:7.3-apache

ENV COMPOSER_ALLOW_SUPERUSER=1
ENV TZ=America/Santiago

# Install utilities + PHP extensions needed by Joomla / VirtueMart / the Webpay
# plugin, Node 22.x (active LTS, used to generate docs/INSTALLATION.pdf with
# markdown-pdf) without executing remote setup scripts, and Composer
RUN apt-get update && apt-get install -y --no-install-recommends \
    ca-certificates \
    curl \
    git \
    gnupg \
    less \
    libfreetype6-dev \
    libjpeg62-turbo-dev \
    libpng-dev \
    libxml2-dev \
    libzip-dev \
    mariadb-client \
    nano \
    ripgrep \
    ssh \
    unzip \
    wget \
    zip \
 && docker-php-ext-configure gd --with-freetype-dir=/usr --with-jpeg-dir=/usr \
 && docker-php-ext-install -j"$(nproc)" gd mysqli soap zip \
 && a2enmod rewrite \
 && ln -snf /usr/share/zoneinfo/"$TZ" /etc/localtime \
 && echo "$TZ" > /etc/timezone \
 && mkdir -p /etc/apt/keyrings \
 && curl --proto "=https" -fsSL https://deb.nodesource.com/gpgkey/nodesource-repo.gpg.key \
    | gpg --dearmor -o /etc/apt/keyrings/nodesource.gpg \
 && echo "deb [signed-by=/etc/apt/keyrings/nodesource.gpg] https://deb.nodesource.com/node_22.x nodistro main" \
    > /etc/apt/sources.list.d/nodesource.list \
 && apt-get update \
 && apt-get install -y --no-install-recommends nodejs \
 && corepack enable \
 && corepack prepare pnpm@11.20.0 --activate \
 && rm -rf /var/lib/apt/lists/* \
 && php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');" \
 && EXPECTED_SIGNATURE="$(curl --proto "=https" -fsSL https://composer.github.io/installer.sig)" \
 && ACTUAL_SIGNATURE="$(php -r "echo hash_file('sha384', 'composer-setup.php');")" \
 && if [ "$EXPECTED_SIGNATURE" != "$ACTUAL_SIGNATURE" ]; then \
        echo "ERROR: Invalid Composer installer signature" >&2; \
        rm composer-setup.php; \
        exit 1; \
    fi \
 && php composer-setup.php --install-dir=/usr/local/bin --filename=composer \
 && php -r "unlink('composer-setup.php');" \
 && composer --version \
 && git config --global --add safe.directory /workspace

# PHP settings needed by VirtueMart / the Webpay plugin (uploads, execution time)
COPY .devcontainer/files/php-virtuemart.ini /usr/local/etc/php/conf.d/zz-virtuemart.ini

# Offline installer bundle for Joomla 3.8 + VirtueMart 3.2, taken from the
# opentools/docker-virtuemart:j3vm3 image. The entrypoint copies it into the
# (persisted) webroot volume on first boot, the same way the official
# "wordpress" image seeds /var/www/html.
COPY --from=vmsrc /usr/src/virtuemart /usr/src/virtuemart
COPY --from=vmsrc /makedb.php /makedb.php

# Copy the shell settings / entrypoint to make the shop available
COPY .devcontainer/vm-entrypoint.sh /usr/local/bin/vm-entrypoint.sh
RUN chmod +x /usr/local/bin/vm-entrypoint.sh

WORKDIR /workspace

ENTRYPOINT ["vm-entrypoint.sh"]
CMD ["apache2-foreground"]

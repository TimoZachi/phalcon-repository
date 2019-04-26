ARG PHP_MINOR_VERSION=7.3
FROM php:${PHP_MINOR_VERSION}-cli-stretch

# Re-declare the arg so that it can be used in the rest of the dockerfile
ARG PHP_MINOR_VERSION

# Chosen php ini file depends on the environment specified here, can be either development or production
ARG PROJECT_ENV=development

# Phalcon framework version
ARG PHALCON_FRAMEWORK_VERSION=3.4.3

# Group and user id
ARG PGID=1000
ARG PUID=1000

# Remove default html folder
RUN rm -rf /var/www/html \
    # Istall curl and git
    && apt-get update --yes --quiet && apt-get upgrade --yes --quiet \
    && apt-get install --yes --no-install-recommends apt-utils curl git sudo vim nano \
    # Copy php ini configuration based on environment
    && cp "/usr/local/etc/php/php.ini-${PROJECT_ENV}" /usr/local/etc/php/php.ini \
    # zip
    && apt-get install --yes --no-install-recommends libzip-dev unzip \
    && docker-php-ext-install zip \
    # Mysql
    && docker-php-ext-install mysqli pdo_mysql \
    # XDebug
    && pecl install xdebug \
    && docker-php-ext-enable xdebug \
    # Phalcon framework
    && currentDir=$(pwd) \
    && tempDir=$(mktemp -d) \
    && curl -sSL -o "$tempDir/cphalcon" https://codeload.github.com/phalcon/cphalcon/tar.gz/v"$PHALCON_FRAMEWORK_VERSION" \
    && tar -C "$tempDir" -xzf "$tempDir/cphalcon" \
    && cd "$tempDir"/cphalcon-"$PHALCON_FRAMEWORK_VERSION"/build \
    && ./install --phpize /usr/local/bin/phpize --php-config /usr/local/bin/php-config \
    && docker-php-ext-enable phalcon \
    && cd "$currentDir" \
    && rm -rf "$tempDir" \
    # Create a user for composer and make it sudoable without a password prompt
    && groupadd --gid=${PGID} docker \
    && useradd --gid=${PGID} --uid=${PUID} --shell=/bin/bash --create-home docker \
    # Make user sudoable
    && echo "docker ALL=(ALL) NOPASSWD:ALL" >> /etc/sudoers \
    # Install composer
    && curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer \
    # Cleanup
    && apt-get clean \
    && apt-get --yes --purge autoremove \
    && rm -rf /var/lib/apt/lists/* /tmp/* /var/tmp/*

ENV PATH="$PATH:/home/docker/.composer/vendor/bin"

# Switch to user docker to install composer packages
USER docker

# Faster composer install inside the container with hirak/prestissimo plugin
RUN composer global require --no-suggest --no-interaction --no-progress hirak/prestissimo phalcon/devtools \
    && mv /home/docker/.composer/vendor/bin/phalcon.php /home/docker/.composer/vendor/bin/phalcon

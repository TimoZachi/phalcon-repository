ARG PHP_VERSION=7.2
FROM php:${PHP_VERSION}-cli-stretch

# Remove default html folder
RUN rm -rf /var/www/html

# Istall curl and git
RUN apt-get update -yqq \
    && apt-get install --yes --no-install-recommends apt-utils curl git sudo

# Create ini file from defaults
ARG PHP_INI_ENVIRONMENT=development
RUN cp "/usr/local/etc/php/php.ini-${PHP_INI_ENVIRONMENT}" /usr/local/etc/php/php.ini

##########################################################################################
## PHP DEPENDENCIES
##########################################################################################

# zip
RUN apt-get update -yqq \
    && apt-get install --yes --no-install-recommends libzip-dev unzip \
    && docker-php-ext-install zip

# mysql
RUN docker-php-ext-install mysqli pdo_mysql

# xdebug
RUN pecl install xdebug \
    && docker-php-ext-enable xdebug

##########################################################################################
## PHALCON FRAMEWORK
##########################################################################################

ARG PHALCON_FRAMEWORK_VERSION=3.4.3
RUN cd /tmp \
    && curl -sSLO https://codeload.github.com/phalcon/cphalcon/tar.gz/v${PHALCON_FRAMEWORK_VERSION} \
    && tar xvzf v${PHALCON_FRAMEWORK_VERSION} \
    && cd cphalcon-${PHALCON_FRAMEWORK_VERSION}/build \
    && ./install --phpize /usr/local/bin/phpize --php-config /usr/local/bin/php-config \
    && cd /tmp \
    && rm -rf cphalcon-${PHALCON_FRAMEWORK_VERSION} \
    && rm -rf v${PHALCON_FRAMEWORK_VERSION} \
    && docker-php-ext-enable phalcon

##########################################################################################
## MACHINE CONFIGURATION
##########################################################################################

ARG PGID=1000
ARG PUID=1000
# Create a user for composer and make it sudoable without password prompt
RUN groupadd --gid=${PGID} docker \
    && useradd --gid=${PGID} --uid=${PUID} --shell=/bin/bash --create-home docker \
    && echo "docker ALL=(ALL) NOPASSWD:ALL" >> /etc/sudoers

##########################################################################################
## COMPOSER
##########################################################################################

# Install composer
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

ENV PATH="${PATH}:/home/docker/.composer/vendor/bin"

# Switch to user docker to install composer packages
USER docker

# Faster composer install inside the container with hirak/prestissimo plugin
RUN composer global require --no-suggest --no-interaction --no-progress hirak/prestissimo phalcon/devtools \
    && mv /home/docker/.composer/vendor/bin/phalcon.php /home/docker/.composer/vendor/bin/phalcon

# Switch back to root
USER root

##########################################################################################
## CLEANUP
##########################################################################################

# Clean-up image
RUN apt-get clean \
    && rm -rf /var/lib/apt/lists/* /tmp/* /var/tmp/*

##########################################################################################
## FINAL SETUP
##########################################################################################

# Set the default user to docker
USER docker

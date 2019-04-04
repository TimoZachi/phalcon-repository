ARG PHP_VERSION=7.2
FROM php:${PHP_VERSION}-cli-stretch

# Remove default html folder
RUN rm -rf /var/www/html

# Update apt-get
RUN apt-get update -yqq

# Istall curl and git
RUN apt-get install -y curl git

# Create ini file from defaults
ARG PHP_INI_ENVIRONMENT=development
RUN cp "/usr/local/etc/php/php.ini-${PHP_INI_ENVIRONMENT}" /usr/local/etc/php/php.ini

##########################################################################################
## PHP DEPENDENCIES
##########################################################################################

# zip
RUN apt-get install -y libzip-dev \
    && apt-get install unzip \
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
# Create a user for composer
RUN groupadd --gid=${PGID} docker \
    && useradd --gid=${PGID} --uid=${PUID} --shell=/bin/bash --create-home docker

# @todo Make the user rootable (via sudo)

# Change folder owner to docker user
RUN mkdir /opt/phalcon-repository \
    && chown docker:docker -R /opt/phalcon-repository

##########################################################################################
## COMPOSER
##########################################################################################

# Install composer
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

ENV PATH="${PATH}:/home/docker/.composer/vendor/bin"

# Switch to user docker to install composer packages
USER docker

# Faster composer install inside the container
RUN composer global require hirak/prestissimo \
    && composer global require phpunit/phpunit \
    && composer global require phalcon/devtools \
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

# Change workdir
WORKDIR /opt/phalcon-repository

# Set the default user to docker
USER docker

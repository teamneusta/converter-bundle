ARG docker_registry=docker-repository.intern.neusta.de
FROM ${docker_registry}/mlocati/php-extension-installer AS php-extension-installer
FROM ${docker_registry}/composer AS composer
FROM ${docker_registry}/php:8.1.0-cli-alpine

COPY --from=php-extension-installer /usr/bin/install-php-extensions /usr/bin/
COPY --from=composer /usr/bin/composer /usr/bin/composer

RUN set -eux; \
    mkdir /app; \
    install-php-extensions xdebug;

COPY php.ini /usr/local/etc/php/

WORKDIR /app

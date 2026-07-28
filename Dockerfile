FROM php:8.3-fpm-alpine AS builder

RUN apk add --no-cache \
    linux-headers \
    build-base \
    autoconf \
    gcc \
    g++ \
    libzip-dev

RUN docker-php-ext-install \
    pdo \
    pdo_mysql \
    intl \
    bcmath \
    zip

COPY composer.json composer.lock ./
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer
RUN composer install --no-dev --optimize-autoloader --prefer-dist

FROM php:8.3-fpm-alpine

LABEL maintainer="GlamByMariga <ops@glambymriga.com>"
LABEL version="1.0.0"

RUN apk add --no-cache \
    libzip-dev \
    openssl \
    ca-certificates

RUN docker-php-ext-install \
    pdo \
    pdo_mysql \
    intl \
    bcmath \
    zip

COPY docker/php.ini /usr/local/etc/php/conf.d/app.ini
COPY docker/php-fpm.conf /usr/local/etc/php-fpm.d/www.conf

RUN addgroup -g 1000 appuser && \
    adduser -D -u 1000 -G appuser appuser

COPY --from=builder --chown=appuser:appuser /app /app
COPY --from=builder --chown=appuser:appuser /vendor /app/vendor

COPY --chown=appuser:appuser . /app

WORKDIR /app

HEALTHCHECK --interval=30s --timeout=3s --start-period=40s --retries=3 \
    CMD php -r "exit(@fsockopen('127.0.0.1', 9000) ? 0 : 1);"

USER appuser

EXPOSE 9000

ENTRYPOINT ["php-fpm"]

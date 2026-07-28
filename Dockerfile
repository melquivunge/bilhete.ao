# syntax=docker/dockerfile:1

# ---------------------------------------------------------------------------
# base — PHP 8.4-FPM com as extensões exigidas pela stack.
#
# A escolha do PHP 8.4, e não do 8.5 do host, está justificada em
# docs/decisions/ADR-001-monolito-laravel-modular.md.
# ---------------------------------------------------------------------------
# Tag de patch fixa, e não php:8.4-fpm-bookworm: o composer.json fixa
# platform.php em 8.4.23, e uma tag flutuante faria o container divergir desse
# valor num rebuild futuro sem qualquer aviso.
FROM php:8.4.23-fpm-bookworm AS base

# libpq-dev  → pdo_pgsql   | libicu-dev → intl (exigida pelo filament/support)
# libzip-dev → zip         | libfcgi-bin → cgi-fcgi, usado pelo healthcheck
RUN apt-get update && apt-get install --no-install-recommends -y \
        libpq-dev \
        libicu-dev \
        libzip-dev \
        libfcgi-bin \
        unzip \
        git \
    && rm -rf /var/lib/apt/lists/*

# pcntl é exigida pelo laravel/horizon (C0.7) e é instalada aqui, e não no ciclo
# em que o Horizon entra, para a falta não rebentar dois ciclos à frente.
# posix, também exigida pelo Horizon, já vem compilada na imagem oficial; por
# isso não consta desta lista, mas consta da asserção mais abaixo.
RUN docker-php-ext-configure intl \
    && docker-php-ext-install -j"$(nproc)" \
        bcmath \
        intl \
        pcntl \
        pdo_pgsql \
        zip

RUN pecl install redis && docker-php-ext-enable redis

# Asserção de build: se alguma extensão faltar, a imagem não é construída.
# É o mesmo critério que fecha o ciclo C0.3, aplicado no próprio build.
RUN for ext in bcmath intl pcntl pdo_pgsql posix redis zip; do \
        php -m | grep -qx "$ext" || { echo "extensão em falta: $ext"; exit 1; }; \
    done

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

COPY docker/php/php.ini /usr/local/etc/php/conf.d/bilhete.ini
COPY docker/php/www.conf /usr/local/etc/php-fpm.d/zz-bilhete.conf

# ---------------------------------------------------------------------------
# development — utilizador não-root e código montado em bind mount.
# ---------------------------------------------------------------------------
FROM base AS development

ARG UID=1000
ARG GID=1000

RUN groupadd --gid "${GID}" app \
    && useradd --uid "${UID}" --gid "${GID}" --create-home --shell /bin/bash app

WORKDIR /var/www/html

USER app

EXPOSE 9000

CMD ["php-fpm"]

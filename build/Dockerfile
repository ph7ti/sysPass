# Build context must be the repository root:
#   docker build -f build/Dockerfile -t syspass:latest .
FROM php:8.2.32-fpm-bookworm

ARG DEBIAN_FRONTEND=noninteractive
ARG SYSPASS_UID=10001
ARG SYSPASS_GID=10001

RUN groupadd --gid "${SYSPASS_GID}" syspass \
    && useradd --uid "${SYSPASS_UID}" --gid syspass --home-dir /var/www/syspass --shell /usr/sbin/nologin syspass

RUN apt-get update \
    && apt-get install -y --no-install-recommends \
        ca-certificates \
        curl \
        gettext \
        libfreetype6-dev \
        libjpeg62-turbo-dev \
        libldap2-dev \
        libpng-dev \
        libzip-dev \
        nginx \
    && rm -rf /var/lib/apt/lists/*

RUN docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j"$(nproc)" \
        gd \
        gettext \
        ldap \
        mbstring \
        opcache \
        pdo_mysql \
        zip

COPY --from=composer:2.7.7 /usr/bin/composer /usr/local/bin/composer

WORKDIR /var/www/syspass

COPY composer.json composer.lock ./
RUN composer install \
        --no-dev \
        --no-interaction \
        --no-progress \
        --no-scripts \
        --no-autoloader \
        --prefer-dist

COPY . .

RUN composer dump-autoload --no-dev --optimize \
    && mkdir -p \
        app/config \
        app/cache \
        app/backup \
        app/temp \
        app/plugins \
    && chown -R syspass:syspass /var/www/syspass

COPY build/php.ini /usr/local/etc/php/conf.d/zz-syspass.ini
COPY build/php-fpm.conf /usr/local/etc/php-fpm.d/zz-syspass.conf
COPY build/nginx.conf /etc/nginx/nginx.conf
COPY build/syspass-entrypoint.sh /usr/local/bin/syspass-entrypoint

RUN chmod 0755 /usr/local/bin/syspass-entrypoint \
    && mkdir -p /tmp/nginx/client_body /tmp/nginx/proxy /tmp/nginx/fastcgi /tmp/nginx/uwsgi /tmp/nginx/scgi \
    && chown -R syspass:syspass /tmp/nginx \
    && nginx -t -c /etc/nginx/nginx.conf

USER syspass

EXPOSE 8080

HEALTHCHECK --interval=30s --timeout=5s --start-period=20s --retries=3 \
    CMD curl -fsS http://127.0.0.1:8080/health || exit 1

ENTRYPOINT ["/usr/local/bin/syspass-entrypoint"]

FROM ubuntu:24.04

ENV DEBIAN_FRONTEND=noninteractive \
    APP_ROOT=/usr/share/sonar_poller

WORKDIR ${APP_ROOT}

RUN apt-get update \
    && apt-get install -y --no-install-recommends \
        ca-certificates \
        composer \
        fping \
        git \
        libcap2-bin \
        nginx \
        openssl \
        php-pear \
        php8.3-cli \
        php8.3-common \
        php8.3-dev \
        php8.3-fpm \
        php8.3-gmp \
        php8.3-mbstring \
        php8.3-sqlite3 \
        php8.3-xml \
        php8.3-zip \
        snmp \
        supervisor \
        unzip \
    && pecl channel-update pecl.php.net \
    && printf "\n" | pecl install ev \
    && echo "extension=ev.so" > /etc/php/8.3/mods-available/ev.ini \
    && phpenmod ev \
    && setcap cap_net_raw+ep /usr/bin/fping \
    && ln -sf /usr/bin/fping /usr/local/sbin/fping \
    && rm -rf /var/lib/apt/lists/*

COPY composer.json composer.lock ./
RUN composer install --no-dev --prefer-dist --no-interaction --no-progress --no-scripts

COPY . .

RUN mkdir -p permanent_config logs \
    && touch permanent_config/database \
    && cp -R vendor/sonarsoftwareinc/external_tool_template/assets/* www/ \
    && cp docker/nginx.conf /etc/nginx/sites-available/default \
    && cp docker/supervisord.conf /etc/supervisor/conf.d/sonar-poller.conf \
    && if [ -d .git ]; then git describe --tags > version; else echo "docker" > version; fi

EXPOSE 80

CMD ["/usr/bin/supervisord", "-n", "-c", "/etc/supervisor/supervisord.conf"]

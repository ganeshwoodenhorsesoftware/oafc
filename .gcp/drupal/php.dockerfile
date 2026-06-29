ARG PROXY_URL=""
FROM ${PROXY_URL}library/composer:2.2 AS composer
FROM ${PROXY_URL}library/php:8.1-fpm AS php-base

RUN mkdir -p -v -m775 /app/web/sites/default/files
RUN mkdir -p -v -m775 /app/private
WORKDIR /app

# Install node and npm:
ENV NODE_VERSION 18.20.6
RUN ARCH= && dpkgArch="$(dpkg --print-architecture)" \
  && case "${dpkgArch##*-}" in \
    amd64) ARCH='x64';; \
    ppc64el) ARCH='ppc64le';; \
    s390x) ARCH='s390x';; \
    arm64) ARCH='arm64';; \
    armhf) ARCH='armv7l';; \
    i386) ARCH='x86';; \
    *) echo "unsupported architecture"; exit 1 ;; \
  esac \
  && curl -fsSLO --compressed "https://nodejs.org/dist/v$NODE_VERSION/node-v$NODE_VERSION-linux-$ARCH.tar.xz" \
  && tar -xJf "node-v$NODE_VERSION-linux-$ARCH.tar.xz" -C /usr/local --strip-components=1 --no-same-owner \
  && rm "node-v$NODE_VERSION-linux-$ARCH.tar.xz" \
  && ln -s /usr/local/bin/node /usr/local/bin/nodejs \
  # smoke tests
  && node --version \
  && npm --version

RUN apt-get update && apt-get install -y --no-install-recommends \
  libfreetype6-dev \
  libjpeg-dev \
  libpng-dev \
  libpq-dev \
  libwebp-dev \
  libzip-dev \
  libicu-dev \
  git \
  default-mysql-client \
  unzip \
  msmtp \
  && docker-php-ext-configure gd \
      --with-freetype \
      --with-jpeg=/usr \
      --with-webp \
  && docker-php-ext-install -j "$(nproc)" \
      bcmath \
      gd \
      opcache \
      pdo_mysql \
      pdo_pgsql \
      zip \
      mysqli \
      pdo \
  && docker-php-ext-enable pdo_mysql \
  && docker-php-ext-install intl \
  && apt-get purge -y --auto-remove -o APT::AutoRemove::RecommendsImportant=false \
  && rm -rf /var/lib/apt/lists/*

FROM php-base AS build
COPY --from=composer /usr/bin/composer /usr/bin/composer
COPY config /app/config
COPY composer.* /app/
COPY patches /app/patches

COPY web /app/web

RUN export COMPOSER_HOME="$(mktemp -d)"; \
  composer install --no-dev  --no-interaction; \
  composer check-platform-reqs; \
  rm -rf "$COMPOSER_HOME"

# Compile themes.
RUN cd /app/web/themes/custom/oafc && npm i && npm run compile && cd -

FROM php-base AS runtime
ARG EMAIL_SERVER_HOST
ARG EMAIL_SERVER_PORT
ARG EMAIL_SERVER_USER
ARG EMAIL_SERVER_PASSWORD
ARG EMAIL_FROM
COPY --from=build /app /app
COPY .gcp/drupal/php.ini /usr/local/etc/php/
RUN echo "sendmail_path = /usr/bin/msmtp -t -i" > /usr/local/etc/php/conf.d/sendmail.ini
RUN echo "defaults" > /etc/msmtprc && \
    echo "auth on" >> /etc/msmtprc && \
    echo "tls on" >> /etc/msmtprc && \
    echo "tls_trust_file /etc/ssl/certs/ca-certificates.crt" >> /etc/msmtprc && \
    echo "account default" >> /etc/msmtprc && \
    echo "host \"${EMAIL_SERVER_HOST}\"" >> /etc/msmtprc && \
    echo "port \"${EMAIL_SERVER_PORT}\"" >> /etc/msmtprc && \
    echo "user \"${EMAIL_SERVER_USER}\"" >> /etc/msmtprc && \
    echo "password \"${EMAIL_SERVER_PASSWORD}\"" >> /etc/msmtprc && \
    echo "from \"${EMAIL_FROM}\"" >> /etc/msmtprc && \
    chmod 600 /etc/msmtprc && \
    chown 1000:0 /etc/msmtprc
RUN { \
        echo 'opcache.memory_consumption=128'; \
        echo 'opcache.interned_strings_buffer=8'; \
        echo 'opcache.max_accelerated_files=4000'; \
        echo 'opcache.revalidate_freq=60'; \
    } > /usr/local/etc/php/conf.d/opcache-recommended.ini

EXPOSE 9000
CMD ["php-fpm"]

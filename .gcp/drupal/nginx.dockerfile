ARG PROXY_URL=""
ARG PHP_IMAGE
FROM ${PHP_IMAGE} as php-fpm-image
FROM ${PROXY_URL}library/nginx:alpine
COPY --from=php-fpm-image /app /app
WORKDIR /app/web
EXPOSE 8080

FROM php:8.3-cli-alpine as symfony_app
RUN apk add --no-cache git zip bash
RUN docker-php-ext-install pdo_mysql
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Setup php app user
ARG USER_ID=1000
RUN adduser -u ${USER_ID} -D -H app
USER app

COPY --chown=app . /app
WORKDIR /app

EXPOSE 3813

CMD ["php", "-S", "0.0.0.0:3813", "-t", "public"]

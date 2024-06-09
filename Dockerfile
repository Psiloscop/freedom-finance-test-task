FROM php:8.3-cli-alpine as symfony_app

RUN apk update
RUN apk add --no-cache bash openrc

## AMQP installaztion
RUN apk --no-cache add build-base \
        autoconf \
        rabbitmq-c-dev
RUN pecl install amqp
RUN docker-php-ext-enable amqp
## AMQP installaztion

RUN docker-php-ext-install pdo_mysql bcmath

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

## OpenRC initialization
RUN openrc
RUN touch /run/openrc/softlevel
RUN sed -i 's/VSERVER/DOCKER/Ig' /lib/rc/sh/init.sh

# configured /etc/rc.conf for docker
RUN sed -i '/getty/d' /etc/inittab

# included the volume rc complains about
VOLUME ["/sys/fs/cgroup"]

COPY ./openrc/symfony-worker /etc/init.d
RUN chmod +x /etc/init.d/symfony-worker
RUN rc-update add symfony-worker default
RUN #rc-service symfony-worker start
## OpenRC initialization

COPY . /app
WORKDIR /app

EXPOSE 3813

CMD ["php", "-S", "0.0.0.0:3813", "-t", "public"]

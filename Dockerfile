FROM php:8.3-cli-alpine as symfony_app

RUN apk update
RUN apk add --no-cache bash openrc

RUN apk --no-cache add build-base \
        autoconf \
        rabbitmq-c-dev
RUN pecl install amqp
RUN docker-php-ext-enable amqp

RUN docker-php-ext-install pdo_mysql bcmath

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer


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
RUN #rc-service symfony-worker start 2>> null


# Setup php app user
ARG USER_ID=1000
RUN adduser -u ${USER_ID} -D -H app
USER app

COPY --chown=app . /app
WORKDIR /app

EXPOSE 3813

CMD ["php", "-S", "0.0.0.0:3813", "-t", "public"]

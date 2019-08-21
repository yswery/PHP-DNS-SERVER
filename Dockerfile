FROM php:alpine

# PHP dependencies
RUN docker-php-ext-install sockets
RUN apk add --no-cache libcap

# copy all files and create users
RUN addgroup -S php && adduser -S php -G php
RUN mkdir -p /home/php/dns/
WORKDIR /home/php/dns/
COPY --chown=php:php . /home/php/dns/

# set server vars
ENV SERVER_IP=0.0.0.0
ENV SERVER_PORT=53
ENV RADIO_DOMAIN=radio.example.com
ENV ALLOWED_DOMAIN=home.example.com

# open port
EXPOSE 53/udp
RUN setcap CAP_NET_BIND_SERVICE=+eip /usr/local/bin/php

# run
CMD ["php","/home/php/dns/hamaserver.php"]
USER php

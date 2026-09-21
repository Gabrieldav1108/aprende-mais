FROM node:22-alpine

RUN apk add --no-cache \
    php84 \
    php84-phar \
    php84-openssl \
    php84-mbstring \
    php84-tokenizer \
    php84-xml \
    php84-ctype \
    php84-fileinfo \
    php84-pdo \
    php84-pdo_mysql \
    php84-bcmath \
    php84-dom \
    php84-curl \
    php84-session \
    php84-simplexml \
    php84-xmlwriter \
    php84-iconv \
    && ln -s /usr/bin/php84 /usr/bin/php

WORKDIR /var/www/html

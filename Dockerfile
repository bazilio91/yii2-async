FROM php:cli

RUN apt-get update && apt-get install -y redis-server \
    rabbitmq-server librabbitmq-dev
RUN php -r "readfile('https://getcomposer.org/installer');" | php
RUN docker-php-ext-install pcntl shmop mbstring
RUN pecl install amqp && echo "extension=amqp.so" >> /usr/local/etc/php/conf.d/amqp.ini

ADD . /var/code

CMD /var/code/tests/docker-test.sh
FROM php:5.6-cli

RUN apt-get update && apt-get install -y librabbitmq-dev
RUN php -r "readfile('https://getcomposer.org/installer');" | php
RUN docker-php-ext-install pcntl shmop mbstring pdo_mysql
RUN pecl install amqp && echo "extension=amqp.so" >> /usr/local/etc/php/conf.d/amqp.ini

ADD . /var/code

CMD /var/code/tests/docker-test.sh
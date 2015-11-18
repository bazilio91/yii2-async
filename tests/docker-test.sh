#!/usr/bin/env bash
set -e
set -x
cd /var/code

redis-server &
rabbitmq-server &

sleep 5

/composer.phar install

vendor/bin/codecept run
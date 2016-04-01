#!/usr/bin/env bash
set -e
set -x
cd /var/code

/composer.phar install

vendor/bin/codecept run
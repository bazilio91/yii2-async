#!/usr/bin/env bash
set -x
#docker rmi yii2-async
set -e

docker-compose build yii2-async
docker-compose run --rm yii2-async
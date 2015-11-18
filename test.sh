#!/usr/bin/env bash
set -x
#docker rmi yii2-async
set -e

docker build -t yii2-async .
docker run --rm yii2-async
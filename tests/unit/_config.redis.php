<?php
$host = getenv('REDIS_PORT_6379_TCP_ADDR') ? getenv('REDIS_PORT_6379_TCP_ADDR') : 'localhost';

return [
    'id' => 'test',
    'basePath' => dirname(__DIR__),
    'components' => [
        'redis' => [
            'class' => 'yii\redis\Connection',
            'hostname' => $host,
            'port' => 6379,
            'database' => 5,
            'connectionTimeout' => 1,
            'dataTimeout' => 1,
        ],
        'async' => [
            'class' => 'bazilio\async\AsyncComponent',
            'transportClass' => 'bazilio\async\transports\AsyncRedisTransport',
            'transportConfig' => [
                'connection' => 'redis',
            ]
        ],
    ]

];
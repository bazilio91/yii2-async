<?php
return [
    'id' => 'test',
    'basePath' => dirname(__DIR__),
    'components' => [
        'redis' => [
            'class' => 'yii\redis\Connection',
            'hostname' => 'localhost',
            'port' => 6379,
            'database' => 5,
        ],
        'async' => [
            'class' => 'bazilio\async\AsyncComponent',
            'transportClass' => 'bazilio\async\transports\AsyncRedisTransport',
            'transportConfig' => [
                'connection' => 'redis',
            ]
        ]
    ]

];
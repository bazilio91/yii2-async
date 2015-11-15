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
        // This components are for blocking receive testing
        'redisFork' => [
            'class' => 'yii\redis\Connection',
            'hostname' => 'localhost',
            'port' => 6379,
            'database' => 5,
        ],
        'asyncFork' => [
            'class' => 'bazilio\async\AsyncComponent',
            'transportClass' => 'bazilio\async\transports\AsyncRedisTransport',
            'transportConfig' => [
                'connection' => 'redisFork',
            ]
        ]
    ]

];
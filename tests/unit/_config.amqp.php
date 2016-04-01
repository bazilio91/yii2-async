<?php
$host = getenv('RABBITMQ_PORT_5672_TCP_ADDR') ? getenv('RABBITMQ_PORT_5672_TCP_ADDR') : 'localhost';

return [
    'id' => 'test',
    'basePath' => dirname(__DIR__),
    'components' => [
        'async' => [
            'class' => 'bazilio\async\AsyncComponent',
            'transportConfig' => [
                'host' => $host,
                'vhost' => '/',
                'exchangeName' => 'test'
            ]
        ]
    ]

];
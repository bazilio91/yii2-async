<?php
$host = getenv('MYSQL_PORT_3306_TCP_ADDR') ? getenv('MYSQL_PORT_3306_TCP_ADDR') : 'localhost';

return [
    'id' => 'test',
    'basePath' => dirname(__DIR__),
    'components' => [
        'db' => [
            'class' => 'yii\db\Connection',
            'dsn' => "mysql:host=$host;dbname=async-test",
            'username' => 'root',
            'password' => '',
            'charset' => 'utf8',
        ],
        'async' => [
            'class' => 'bazilio\async\AsyncComponent',
            'transportClass' => 'bazilio\async\transports\AsyncMysqlTransport',
            'transportConfig' => [
                'connection' => 'db',
            ]
        ]
    ]

];
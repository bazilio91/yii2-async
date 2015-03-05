<?php
return [
    'id' => 'test',
    'basePath' => dirname(__DIR__),
    'components' => [
        'async' => [
            'class' => 'bazilio\async\AsyncComponent',
            'transportConfig' => [
                'vhost' => '/',
                'exchangeName' => 'test'
            ]
        ]
    ]

];
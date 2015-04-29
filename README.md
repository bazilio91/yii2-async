yii2-async
=========
[![Build Status](https://travis-ci.org/bazilio91/yii2-async.svg?branch=master)](https://travis-ci.org/bazilio91/yii2-async)

Provides translucent api for moving large tasks out of request response

Install: `php composer.phar require bazilio/yii2-async:dev-master`

#####Requirments:
- php >=5.4
- Transports:
  - [php-amqp](https://github.com/pdezwart/php-amqp)
  - [yii2-redis](https://github.com/yiisoft/yii2-redis)

#####Using with AMQP:
`php composer.phar require pdezwart/php-amqp:dev-master`

```php
'components' => [
    'async' => [
        'class' => 'bazilio\async\AsyncComponent',
        'transportClass' => 'bazilio\async\transports\AsyncAmqpTransport',
        'transportConfig' => [
            'host' => 'localhost',
            'login' => 'guest',
            'password' => 'guest',
            'vhost' => 'yii',
            'exchangeName' => 'yii'
        ]
    ]
]
```


#####Using with Redis:
`php composer.phar require yiisoft/yii2-redis:*`

```php
'components' => [
    'redis' => [
        'class' => 'yii\redis\Connection',
        'hostname' => 'localhost',
        'port' => 6379,
        'database' => 0,
    ],
    'async' => [
        'class' => 'bazilio\async\AsyncComponent',
        'transportClass' => 'bazilio\async\transports\AsyncRedisTransport',
        'transportConfig' => [
            'connection' => 'redis',
        ]
    ]
]
```



#####Usage:
For code examples look into tests:
- [BaseTestClass](tests/unit/BaseTestClass.php)


######Runing tests:
~~~
vendor/bin/codecept run
~~~

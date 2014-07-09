yii-async
=========

Provides translucent api for moving large tasks out of request response

#####Requirments:
- php >=5.4
- [php-amqp](https://github.com/pdezwart/php-amqp)

#####Using with AMQP:
######Installing:
```php
'components' => [
    'async' => [
        'class' => 'bazilio\async\AsyncComponent',
        'transportConfig' => [
            'transportClass' => 'bazilio\async\transports\AsyncAmqpTransport'
            'host' => 'localhost',
            'login' => 'guest',
            'password' => 'guest',
            'vhost' => 'yii',
            'exchangeName' => 'yii'
        ]
    ]
]
```
######Usage:
For code exampless look into tests:
- [AmqpTest](tests/unit/AmqpTest.php)


######Runing tests:
~~~
vendor/bin/codecept run
~~~

yii2-async
=========
[![Build Status](https://travis-ci.org/bazilio91/yii2-async.svg?branch=master)](https://travis-ci.org/bazilio91/yii2-async)
[![Latest Stable Version](https://poser.pugx.org/bazilio/yii2-async/v/stable)](https://packagist.org/packages/bazilio/yii2-async) 
[![Total Downloads](https://poser.pugx.org/bazilio/yii2-async/downloads)](https://packagist.org/packages/bazilio/yii2-async) 
[![Latest Unstable Version](https://poser.pugx.org/bazilio/yii2-async/v/unstable)](https://packagist.org/packages/bazilio/yii2-async) 
[![License](https://poser.pugx.org/bazilio/yii2-async/license)](https://packagist.org/packages/bazilio/yii2-async)

Provides translucent api for moving large tasks out of request response

Install: `php composer.phar require bazilio/yii2-async:dev-master`

##### Requirments:
- php >=5.4
- Transports:
  - `yii\db\Connection`
  - [php-amqp](https://github.com/pdezwart/php-amqp)
  - [yii2-redis](https://github.com/yiisoft/yii2-redis)

##### Using with AMQP:
`php composer.phar require pdezwart/php-amqp:dev-master`

main.php:
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


##### Using with Redis:
`php composer.phar require yiisoft/yii2-redis:*`

main.php:
```php
'components' => [
    'redis' => [
        'class' => 'yii\redis\Connection',
        'hostname' => 'localhost',
        'port' => 6379,
        'database' => 0,
        'dataTimeout' => -1, // important for daemon and blocking queries
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

##### Using with MySQL (probably any sql, but tested only with mysql)

main.php:
```php
'components' => [
    'db' => [
        'class' => 'yii\db\Connection',
        'dsn' => 'mysql:host=localhost;dbname=yii2advenced',
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
```

Apply migrations:
```php
./yii migrate/up --migrationPath=@vendor/bazilio/yii2-async/migrations
```

##### Usage:

#### Create and send:
Test class example:
```php
class DownloadTask extends AsyncTask
{
    public $url;
    public $file;
    public static $queueName = 'downloads';

    public function execute()
    {
        return file_put_contents($this->file, file_get_contents($this->url));
    }
}

// create task
$task = new DownloadTask(['url' => 'http://localhost/', 'file' => '/tmp/localhost.html']);
\Yii::$app->async->sendTask($task);
```

Or call external method:
```php
$task = new AsyncExecuteTask([
    'class' => 'common\components\MyDownloaderComponent',
    'method' => 'download',
    'arguments' => ['url' => 'http://localhost/', 'file' => '/tmp/localhost.html']
]);


$task::$queueName = 'downloads';

if (YII_ENV !== 'prod') {
    $task->execute();
} else {
    Yii::$app->async->sendTask($task);
}
```

#### Execute:

Bash way:

Fill console config:
```php
'controllerMap' => [
        'async-worker' => [
            'class' => 'bazilio\async\commands\AsyncWorkerCommand',
        ],
    ],
```

Run:
```bash
# Process and exit on finish
./yii async-worker/execute downloads
# Process and wait for new tasks (only redis)
./yii async-worker/daemon downloads
```

Code way:

```php
while ($task = \Yii::$app->async->receiveTask('downloads')) {
    if ($task->execute()) {
        \Yii::$app->async->acknowledgeTask($task);
    }
}
```        

For more code examples look into tests:
- [BaseTestClass](tests/unit/BaseTestClass.php)


###### Runing tests:
~~~
vendor/bin/codecept run
~~~
Or in Docker:
~~~
./test.sh
~~~

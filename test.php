<?php
require 'vendor/autoload.php';
require(__DIR__ . '/vendor/yiisoft/yii2/Yii.php');
use bazilio\async\AsyncComponent;

$application = new yii\console\Application([
    'id' => 'test',
    'basePath' => dirname(__DIR__)
]);

$a = new AsyncComponent(
    [
        'transportConfig' => [
            'vhost' => 'ekabu',
            'exchangeName' => 'ekabu'
        ]
    ]
);


use \yii\codeception\TestCase;
use Yii;

class AmqpTest extends TestCase
{
    public $appConfig = '@tests/unit/_console.php';
}

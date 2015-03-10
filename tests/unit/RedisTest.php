<?php
namespace bazilio\async\tests\unit;

class RedisTest extends BaseTestClass
{
    public $appConfig = '@tests/unit/_config.redis.php';

    public function setUp()
    {
        parent::setUp();
        \Yii::$app->redis->executeCommand('FLUSHDB');
    }
}
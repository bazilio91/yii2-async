<?php
namespace bazilio\async\tests\unit;

use bazilio\async\Exception;
use bazilio\async\models\AsyncExecuteTask;
use bazilio\async\models\AsyncTask;

class TestTask extends AsyncTask
{
    public $id;

    public function execute()
    {
        return $this->id;
    }
}

class TestException extends \Exception
{
}

class BlackHole
{
    public static function run($param)
    {
        throw new TestException($param);
    }

    public static function hintArray(array $array)
    {

    }

    public static function hintClass(TestTask $param)
    {

    }
}

class BaseTestClass extends \yii\codeception\TestCase
{
    public $appConfig = '@tests/unit/_config.amqp.php';
    /**
     * @var \bazilio\async\AsyncComponent
     */
    protected $async;

    public function setUp()
    {
        parent::setUp();

        $this->async = \Yii::$app->async;

        // cleanup
        $this->async->purge(TestTask::$queueName);
        if ($this->async->receiveTask('wrong')) {
            $this->async->purge('wrong');
        }
    }

    protected function tearDown()
    {
        parent::tearDown();

        if (\Yii::$app) {
            $this->async = \Yii::$app->async;

            // cleanup
            $this->async->purge(TestTask::$queueName);
            if ($this->async->receiveTask('wrong')) {
                $this->async->purge('wrong');
            }
        }
    }


    public function testPurge()
    {
        $task = new TestTask();
        $this->async->sendTask($task);

        $task = $this->async->receiveTask(TestTask::$queueName);
        $this->assertEquals(TestTask::className(), get_class($task));

        $this->assertTrue($this->async->purge(TestTask::$queueName));
        $this->assertFalse($this->async->receiveTask(TestTask::$queueName));

        $this->assertTrue($this->async->acknowledgeTask($task));
    }

    public function testLifeCycle()
    {
        $task = new TestTask();
        $task->id = uniqid();

        $this->async->sendTask($task);

        /** @var TestTask $rTask */
        $rTask = $this->async->receiveTask(TestTask::$queueName);
        $this->assertInstanceOf(TestTask::className(), $rTask);

        $this->assertEquals($task->id, $rTask->execute());

        $this->assertTrue($this->async->acknowledgeTask($rTask));

        $this->assertFalse($this->async->receiveTask(TestTask::$queueName));
    }

    public function testAsyncExecuteTaskAgainstClass()
    {
        $aTask = new AsyncExecuteTask();
        $aTask->setAttributes(
            [
                'class' => 'bazilio\async\tests\unit\BlackHole',
                'method' => 'run',
                'arguments' => ['param' => 'through the space']
            ]
        );

        $this->async->sendTask($aTask);

        $rTask = $this->async->receiveTask($aTask::$queueName);
        $this->assertInstanceOf('bazilio\async\models\AsyncExecuteTask', $rTask);

        try {
            $rTask->execute();
        } catch (TestException $e) {
            $this->assertEquals($e->getMessage(), 'through the space');
            $this->assertTrue($this->async->acknowledgeTask($rTask));
            return;
        }

        $this->fail('BlackHole method wasn\'t called.');
    }

    public function testAsyncExecuteTaskAgainstInstance()
    {
        $instance = new TestTask(['id' => 1]);

        $aTask = new AsyncExecuteTask();
        $aTask->setAttributes(
            [
                'instance' => $instance,
                'method' => 'execute',
            ]
        );

        $this->async->sendTask($aTask);

        $rTask = $this->async->receiveTask($aTask::$queueName);
        $this->assertInstanceOf('bazilio\async\models\AsyncExecuteTask', $rTask);


        $this->assertEquals(1, $rTask->execute());
    }

    public function testArgumentsTypeHintingArrayValidation()
    {
        $this->markTestSkipped('Scalar type hint reflection is not available yet.');
    }

    public function testArgumentsTypeHintingClassValidation()
    {
        $aTask = new AsyncExecuteTask();
        $aTask->setAttributes(
            [
                'class' => 'bazilio\async\tests\unit\BlackHole',
                'method' => 'hintClass',
                'arguments' => ['param' => 0]
            ]
        );

        $this->assertFalse($aTask->validate());
        $this->assertEquals(
            $aTask->errors['arguments'][0],
            'Method `hintClass` param `param` expects type `bazilio\async\tests\unit\TestTask` but got integer'
        );
    }

    public function testMissingArgumentsValidation()
    {
        $aTask = new AsyncExecuteTask();
        $aTask->setAttributes(
            [
                'class' => 'bazilio\async\tests\unit\BlackHole',
                'method' => 'run',
                'arguments' => ['fail' => 'through the space']
            ]
        );

        $this->assertFalse($aTask->validate());

        $this->assertEquals(
            $aTask->errors['arguments'][0],
            "Method `run` missing required arguments: param"
        );

        try {
            $this->async->sendTask($aTask);
        } catch (Exception $e) {
            return;
        }

        $this->fail('Async doesn\'t reject invalid task.');
    }

    public function testSubscribe()
    {
        if (get_called_class() == 'bazilio\async\tests\unit\AmqpTest') {
            $this->markTestSkipped('No support for AMQP yet');
            return;
        }

        $task = new TestTask();
        $task->id = 1;

        \Yii::$app->async->sendTask($task);

        $this->assertNotFalse(\Yii::$app->async->receiveTask($task::$queueName, true));
    }
}
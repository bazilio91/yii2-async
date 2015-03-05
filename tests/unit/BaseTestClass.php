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
    }

    public function testPurge()
    {
        $task = new TestTask();
        $this->async->sendTask($task);

        $this->assertEquals(TestTask::className(), get_class($this->async->receiveTask(TestTask::$queueName)));

        $this->assertTrue($this->async->purge(TestTask::$queueName));
        $this->assertFalse($this->async->receiveTask(TestTask::$queueName));
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

    public function testAsyncExecuteTask()
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
            return true;
        }

        $this->fail('BlackHole method wasn\'t called.');
    }

    public function testArgumentsValidation()
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
}
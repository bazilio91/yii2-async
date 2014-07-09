<?php
use bazilio\async\AsyncComponent;
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
    public function run($param)
    {
        throw new TestException($param);
    }
}

class AmqpTest extends \yii\codeception\TestCase
{
    public $appConfig = '@tests/unit/_config.amqp.php';
    /**
     * @var AsyncComponent
     */
    protected $async;

    public function setUp()
    {
        parent::setUp();

        $this->async = Yii::$app->async;
    }

    public function testPurge()
    {
        $task = new TestTask();
        $this->async->sendTask($task);

        $this->assertInstanceOf('TestTask', $this->async->receiveTask(TestTask::$queueName));

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
        $this->assertInstanceOf('TestTask', $rTask);

        $this->assertEquals($task->id, $rTask->execute());

        $this->assertTrue($this->async->acknowledgeTask($rTask));

        $this->assertFalse($this->async->receiveTask(TestTask::$queueName));
    }

    public function testAsyncExecuteTask()
    {
        $aTask = new AsyncExecuteTask();
        $aTask->setAttributes(
            [
                'class' => '\BlackHole',
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
}
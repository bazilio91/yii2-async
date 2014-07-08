<?php

use bazilio\async\AsyncComponent;

class TestTask extends \bazilio\async\models\AsyncTask
{
    public $id;

    public function execute()
    {
        return $this->id;
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
}
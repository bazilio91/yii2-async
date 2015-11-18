<?php

namespace bazilio\async\commands;

use bazilio\async\models\AsyncTask;
use Spork\ProcessManager;


class AsyncWorkerCommand extends \yii\console\Controller
{
    static $state = 1;
    protected $child;

    /**
     * @param string|null $queueName
     * @param int|null $count tasks to process
     */
    public function actionExecute($queueName = null, $count = null)
    {
        $this->handleSignal();
        /** @var AsyncTask $task */
        while ($task = \Yii::$app->async->receiveTask($queueName ?: AsyncTask::$queueName)) {
            $this->checkSignal();

            $this->processTask($task);

            if (($count !== null && !--$count) || $this->checkSignal()) {
                break;
            }
        }
    }

    /**
     * @param string|null $queueName
     * @param int|null $count tasks to process
     */
    public function actionDaemon($queueName = null, $count = null)
    {
        $this->handleSignal();

        /** @var AsyncTask $task */
        while ($task = \Yii::$app->async->receiveTask($queueName ?: AsyncTask::$queueName, true)) {
            $this->checkSignal();

            $task::$queueName = $queueName ?: AsyncTask::$queueName;
            $this->processTask($task);

            if (($count !== null && !--$count) || $this->checkSignal()) {
                break;
            }
        }
    }

    protected function processTask(AsyncTask $task)
    {
        $task->execute();
        \Yii::$app->async->acknowledgeTask($task);

    }

    private function handleSignal()
    {
        pcntl_signal(
            SIGTERM,
            function ($signo) {
                echo "This signal is called. [$signo] \n";
                static::$state = -1;
            }
        );
    }

    private function checkSignal()
    {
        pcntl_signal_dispatch();
        if (AsyncWorkerCommand::$state == -1) {
            return true;
        }
    }
}

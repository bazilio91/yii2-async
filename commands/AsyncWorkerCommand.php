<?php

namespace bazilio\async\commands;

use bazilio\async\models\AsyncTask;


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
        /** @var AsyncTask $task */
        while ($task = \Yii::$app->async->receiveTask($queueName ?: AsyncTask::$queueName, true)) {
            $this->handleSignal();

            $task::$queueName = $queueName ?: AsyncTask::$queueName;
            $this->processTask($task);

            if (($count !== null && !--$count) || $this->checkSignal()) {
                break;
            }

            // we don't want to ignore SIGTERM while waiting for task
            $this->removeSignalHandler();
        }
    }

    protected function processTask(AsyncTask $task)
    {
        $task->execute();
        \Yii::$app->async->acknowledgeTask($task);

    }

    private function handleSignal()
    {
        if (!function_exists('pcntl_signal')) {
            return;
        }

        pcntl_signal(
            SIGTERM,
            function ($signo) {
                echo "This signal is called. [$signo] \n";
                static::$state = -1;
            }
        );
    }

    private function removeSignalHandler()
    {
        if (!function_exists('pcntl_signal')) {
            return;
        }

        pcntl_signal(SIGTERM, SIG_DFL);
    }

    private function checkSignal()
    {
        if (!function_exists('pcntl_signal')) {
            return false;
        }

        pcntl_signal_dispatch();
        if (AsyncWorkerCommand::$state == -1) {
            return true;
        }
    }
}

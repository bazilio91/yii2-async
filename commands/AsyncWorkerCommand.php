<?php

use bazilio\async\models\AsyncTask;

class AsyncWorkerCommand extends \yii\console\Controller
{
    /**
     * @param string|null $queueName
     */
    public function actionExecute($queueName = null)
    {
        /** @var AsyncTask $task */
        while ($task = \Yii::$app->async->receiveTask($queueName ?: AsyncTask::$queueName)) {
            $task->execute();
            \Yii::$app->async->acknowledgeTask($task);
        }
    }

    /**
     * @param string|null $queueName
     */
    public function actionDaemon($queueName = null)
    {
        /** @var AsyncTask $task */
        while ($task = \Yii::$app->asyncAmqp->waitAndReceive($queueName ?: AsyncTask::$queueName)) {
            $task->execute();
            \Yii::$app->asyncAmqp->acknowledgeTask($task);
        }
    }
}
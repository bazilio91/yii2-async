<?php
namespace bazilio\async;

use bazilio\async\models\AsyncTask;
use bazilio\transports\Transport;
use yii\base\Component;
use Yii;

/**
 * Class AsyncComponent
 * @package bazilio\async
 */
class AsyncComponent extends Component
{
    public $transportClass = 'bazilio\async\transports\AsyncAmqpTransport';

    public $transportConfig = [];

    /** @var Transport */
    protected $transport;

    public function init()
    {
        $this->transport = new $this->transportClass($this->transportConfig);
    }

    /**
     * @param AsyncTask $task
     * @return bool|string
     * @throws Exception
     */
    public function sendTask(AsyncTask $task)
    {
        if ($task->validate()) {
            return $this->transport->send(serialize($task), $task::$queueName);
        } else {
            throw new Exception(var_export($task->errors, true));
        }
    }

    /**
     * @param $queueName
     * @return AsyncTask|bool
     */
    public function receiveTask($queueName)
    {
        return $this->transport->receive($queueName);
    }

    /**
     * @param AsyncTask $task
     * @return bool
     */
    public function acknowledgeTask(AsyncTask $task)
    {
        return $this->transport->acknowledge($task);
    }

    /**
     * @param string $queueName
     * @return bool
     */
    public function purge($queueName)
    {
        return $this->transport->purge($queueName);
    }
} 
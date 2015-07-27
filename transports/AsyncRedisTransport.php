<?php
namespace bazilio\async\transports;

use bazilio\async\models\AsyncTask;

/**
 * Class AsyncRedisTransport
 * @package bazilio\async\transports
 *
 *
 */
class AsyncRedisTransport
{
    /**
     * @var \yii\redis\Connection
     */
    protected $connection;

    function __construct(Array $connectionConfig)
    {
        $this->connection = \Yii::$app->{$connectionConfig['connection']};
    }

    public static function getQueueKey($queueName, $progress = false)
    {
        return "queue:$queueName" . ($progress ? ':progress' : null);
    }

    /**
     * @param string $text
     * @param string $queueName
     * @return integer index in queue
     */
    public function send($text, $queueName)
    {
        $return = $this->connection->executeCommand('LPUSH', [self::getQueueKey($queueName), $text]);
        $this->connection->executeCommand('PUBLISH', [self::getQueueKey($queueName), 'new']);
        return $return;
    }

    /**
     * @param string $queueName
     * @return AsyncTask|bool
     */
    public function receive($queueName)
    {
        $message = $this->connection->executeCommand(
            'RPOPLPUSH',
            [self::getQueueKey($queueName), self::getQueueKey($queueName, true)]
        );

        if (!$message) {
            return false;
        }

        /**
         * @var AsyncTask $task
         */
        $task = unserialize($message);
        $task->message = $message;
        return $task;
    }

    /**
     * @param $queueName
     * @return AsyncTask
     */
    public function waitAndReceive($queueName)
    {
        $task = $this->receive($queueName);
        if (!$task) {
            // subscribe to queue events
            $this->connection->executeCommand('SUBSCRIBE', [self::getQueueKey($queueName)]);
            while (!$task) {
                // wait for message
                $response = $this->connection->parseResponse('');
                if (is_array($response)) {
                    if ($response[0] !== 'message') {
                        continue;
                    }

                    // unsubscribe to release redis connection context
                    $this->connection->executeCommand('UNSUBSCRIBE', [self::getQueueKey($queueName)]);
                    $task = $this->receive($queueName);

                    // if someone else got our task - subscribe again and wait
                    if (!$task) {
                        $this->connection->executeCommand('SUBSCRIBE', [self::getQueueKey($queueName)]);
                    }
                }
            }
        }

        return $task;
    }


    /**
     * @param AsyncTask $task
     * @return bool
     */
    public function acknowledge(AsyncTask $task)
    {
        return $this->connection->executeCommand(
            'LREM',
            [self::getQueueKey($task::$queueName, true), -1, $task->message]
        ) === '1';
    }

    /**
     * @param string $queueName
     * @return bool
     */
    public function purge($queueName)
    {
        $this->connection->executeCommand('DEL', [self::getQueueKey($queueName)]);
        return true;
    }

    /**
     * @return bool
     * @throws Exception
     */
    function disconnect()
    {
        if ($this->connection) {
            $this->connection->close();
        }
    }
}

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

    public function __construct(Array $connectionConfig)
    {
        $this->connection = \Yii::$app->{$connectionConfig['connection']};
    }

    public static function getQueueKey($queueName, $progress = false)
    {
        return "queue:$queueName" . ($progress ? ':progress' : null);
    }

    public static function getChannelKey($queueName)
    {
        return "channel:$queueName";
    }

    /**
     * @param string $text
     * @param string $queueName
     * @return integer index in queue
     */
    public function send($text, $queueName)
    {
        $return = $this->connection->executeCommand('LPUSH', [self::getQueueKey($queueName), $text]);
        return $return;
    }

    /**
     * @param string $queueName
     * @param bool $wait Wait for task
     * @return AsyncTask|bool
     * @throws Exception
     */
    public function receive($queueName, $wait = false)
    {
        $params = [self::getQueueKey($queueName), self::getQueueKey($queueName, true)];
        if ($wait) {
            $params[] = 0;
        }
        $message = $this->connection->executeCommand(
            ($wait ? 'BRPOPLPUSH' : 'RPOPLPUSH'),
            $params
        );

        if (!$message) {
            return false;
        }

        if (!is_string($message)) {
            throw new Exception('Failed to assert message is a string: ' . var_export($message, true));
        }

        /**
         * @var AsyncTask $task
         */
        $task = unserialize($message);
        $task->message = $message;
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
    public function disconnect()
    {
        if ($this->connection) {
            $this->connection->close();
        }
    }
}

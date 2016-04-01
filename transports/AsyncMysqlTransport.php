<?php
namespace bazilio\async\transports;

use bazilio\async\models\AsyncTask;

/**
 * Class AsyncMysqlTransport
 * @package bazilio\async\transports
 *
 * Apply migrations before use: ./yii migrate/up --migrationPath=@vendor/bazilio/yii2-async/migrations
 */
class AsyncMysqlTransport
{
    const STATUS_NEW = 0;
    const STATUS_PROGRESS = 1;
    /**
     * @var \yii\db\Connection
     */
    protected $connection;

    public $tableName = '{{%async}}';

    public function __construct(Array $connectionConfig)
    {
        $this->connection = \yii\di\Instance::ensure(
            \Yii::$app->{$connectionConfig['connection']},
            \yii\db\Connection::className()
        );
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
        return $this->connection->createCommand()->insert(
            $this->tableName,
            [
                'queue' => self::getQueueKey($queueName),
                'data' => $text,
            ]
        )->execute() == 1;
    }

    /**
     * @param string $queueName
     * @param bool $wait Wait for task
     * @return AsyncTask|bool
     * @throws Exception
     */
    public function receive($queueName, $wait = false)
    {
        $transaction = $this->connection->beginTransaction();

        $message = (new \yii\db\Query())
            ->select('*')
            ->from($this->tableName)
            ->where(['status' => self::STATUS_NEW, 'queue' => self::getQueueKey($queueName)])
            ->limit(1)
            ->one($this->connection);

        if (!$message || $this->connection->createCommand()
                ->update(
                    $this->tableName,
                    ['status' => self::STATUS_PROGRESS],
                    [
                        'id' => $message['id'],
                        'status' => self::STATUS_NEW,
                    ]
                )->execute() !== 1
        ) {
            $transaction->rollBack();
            return false;
        };

        $transaction->commit();

        /**
         * @var AsyncTask $task
         */
        $task = unserialize($message['data']);
        $task->message = $message;
        return $task;
    }

    /**
     * @param AsyncTask $task
     * @return bool
     */
    public function acknowledge(AsyncTask $task)
    {
        return $this->connection->createCommand()->delete(
            $this->tableName,
            [
                'id' => $task->message['id'],
            ]
        )->execute() == 1;
    }

    /**
     * @param string $queueName
     * @return bool
     */
    public function purge($queueName)
    {
        $this->connection->createCommand()->delete(
            $this->tableName,
            [
                'queue' => $queueName
            ]
        );
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

            $this->connection = null;
        }
    }
}

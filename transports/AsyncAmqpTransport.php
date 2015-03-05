<?php
namespace bazilio\async\transports;

use bazilio\async\models\AsyncTask;
use bazilio\async\transports\Exception;

class AsyncAmqpTransport
{
    protected $connection;
    /**
     * @var \AMQPChannel $channel
     */
    protected $channel;
    /**
     * @var \AMQPExchange $exchange
     */
    protected $exchange;
    /**
     * @var \AMQPQueue[]
     */
    protected $queues;

    protected $routingKey = 'tasks';
    public $host = 'localhost';

    public $connectionConfig = [
        'host' => 'localhost',
        'login' => 'guest',
        'password' => 'guest',
        'vhost' => 'yii',
        'exchangeName' => 'yii'
    ];

    function __construct($connectionConfig)
    {
        $this->connectionConfig = array_merge($this->connectionConfig, $connectionConfig);
        $this->getConnection();
    }


    private function getConnection()
    {
        if (!$this->connection || !$this->connection->isConnected()) {
            $this->connection = new \AMQPConnection($this->connectionConfig);
            try {
                $this->connection->connect();

                if (!$this->connection->isConnected()) {
                    throw new Exception("Can't initialize connection to amqp server!");
                }
            } catch (\AMQPConnectionException $e) {
                throw new Exception($e->getMessage());
            }
        }

        return $this->connection;
    }

    private function getChannel()
    {
        if (!$this->channel || !$this->channel->isConnected()) {
            $this->channel = new \AMQPChannel($this->getConnection());
        }

        return $this->channel;
    }

    private function getExchange()
    {
        if (!$this->exchange) {
            $this->exchange = new \AMQPExchange($this->getChannel());
            $this->exchange->setFlags(AMQP_DURABLE);
        }

        return $this->exchange;
    }

    public function getQueue($queueName)
    {

        if (!$this->queues[$queueName]) {
            $queue = new \AMQPQueue($this->getChannel());
            $queue->setName($queueName);
            $queue->setFlags(AMQP_DURABLE);
            $queue->declareQueue();
            $queue->bind('amq.direct', $this->routingKey);
            $this->queues[$queueName] = $queue;
        }

        return $this->queues[$queueName];
    }

    /**
     * @return bool
     * @throws Exception
     */
    public function disconnect()
    {
        if (!$this->connection->disconnect()) {
            throw new Exception('Could not disconnect!');
        }

        return true;
    }

    /**
     * @param string $text
     * @param string $queueName
     * @throws Exception
     * @return bool|string false or message_id
     */
    public function send($text, $queueName)
    {
        $this->getExchange();

        $this->exchange->setName('amq.direct');
        $attributes = ['message_id' => uniqid()];
        $message = $this->exchange->publish($text, $queueName, AMQP_NOPARAM, $attributes);
        if (!$message) {
            throw new Exception("Error: Message '" . $message . "' was not sent.\n");
        }

        return $attributes['message_id'];
    }

    /**
     * @param string $queueName
     * @return AsyncTask|bool
     */
    public function receive($queueName)
    {
        $message = $this->getQueue($queueName)->get();

        if ($message) {
            $task = unserialize($message->getBody());
            $task->message = $message;

            return $task;
        }

        return false;
    }


    /**
     * @param AsyncTask $task
     * @return bool
     */
    public function acknowledge(AsyncTask $task)
    {
        $queue = $this->getQueue($task::$queueName);

        return $queue->ack($task->message->getDeliveryTag());
    }

    /**
     * @param string $queueName
     * @return bool
     */
    public function purge($queueName)
    {
        return $this->getQueue($queueName)->purge();
    }
} 
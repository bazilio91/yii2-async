<?php
namespace bazilio\transports;

use bazilio\async\models\AsyncTask;
use bazilio\async\transports\Exception;

interface Transport
{
    function __construct(Array $connectionConfig);

    /**
     * @return bool
     * @throws Exception
     */
    function disconnect();

    /**
     * @param string $text
     * @param string $queueName
     * @return bool|string false or message_id
     */
    public function send($text, $queueName);

    /**
     * @param string $queueName
     * @return AsyncTask|bool
     */
    public function receive($queueName);

    /**
     * Same as [[receive]] but waits if no tasks in queue
     * @param $queueName
     * @return AsyncTask
     */
    public function waitAndReceive($queueName);

    /**
     * @param AsyncTask $task
     * @return bool
     */
    public function acknowledge(AsyncTask $task);

    /**
     * @param string $queueName
     * @return bool
     */
    public function purge($queueName);
} 
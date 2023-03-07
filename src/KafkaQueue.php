<?php

namespace Buqiu\Kafka;

use Illuminate\Queue\Queue;
use Illuminate\Contracts\Queue\Queue as QueueContract;

class KafkaQueue extends Queue implements QueueContract
{
    protected $producer, $consumer, $config;

    public function __construct($producer, $consumer, $config)
    {
        $this->producer = $producer;
        $this->consumer = $consumer;
        $this->config = $config;
    }

    public function size($queue = null)
    {

    }

    public function push($job, $data = '', $queue = null)
    {
        // 创建 Topic 实例
        $topic = $this->producer->newTopic($queue ?? $this->config['queue']);
        // RD_KAFKA_PARTITION_UA 让 kafka 自由选择分区
        $topic->produce(RD_KAFKA_PARTITION_UA, 0, serialize($job));
        // 队列阻塞最大时长
        $this->producer->flush($this->config['flush_max_ms']);
    }

    public function pushRaw($payload, $queue = null, array $options = [])
    {

    }

    public function later($delay, $job, $data = '', $queue = null)
    {

    }

    public function pop($queue = null)
    {
        // 更新订阅集
        $this->consumer->subscribe([$queue]);

        try {
            // 消费一条消息（参数：等待接收消息的最长时间）
            $message = $this->consumer->consume($this->config['consume_max_ms']);
            match ($message->err) {
                RD_KAFKA_RESP_ERR_NO_ERROR => unserialize($message->payload)->handle(),
                RD_KAFKA_RESP_ERR__PARTITION_EOF => var_dump("No more messages; will wait for more\n"),
                RD_KAFKA_RESP_ERR__TIMED_OUT => var_dump("Timed out\n"),
                default => throw new \Exception($message->errstr(), $message->err),
            };
        } catch (\Exception $e) {
            var_dump($e->getMessage());
        }
    }
}

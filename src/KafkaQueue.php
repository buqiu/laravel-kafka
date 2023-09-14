<?php

declare(strict_types=1);

namespace Buqiu\Kafka;

use Buqiu\Kafka\Serializer\Deserializer;
use Buqiu\Kafka\Serializer\Serializer;
use Illuminate\Contracts\Queue\Queue as QueueContract;
use Illuminate\Queue\Queue;
use Illuminate\Support\Facades\Log;
use Junges\Kafka\Config\Sasl;
use Junges\Kafka\Contracts\KafkaConsumerMessage;
use Junges\Kafka\Facades\Kafka;
use Junges\Kafka\Message\Message;

class KafkaQueue extends Queue implements QueueContract
{
    public function __construct()
    {
    }

    public function size($queue = null)
    {
    }

    /**
     * @param  mixed      $job
     * @param  mixed      $data
     * @param  null|mixed $queue
     * @throws \Exception
     */
    public function push($job, $data = '', $queue = null)
    {
        // 设置 topic
        $producer = Kafka::publishOn($queue);
        // 设置配置项
        $producer->withConfigOptions([
            'api.version.request'      => config('kafka.api_version_request'),
            'acks'                     => config('kafka.acks'),
            'retries'                  => config('kafka.retries'),
            'retry.backoff.ms'         => config('kafka.retry_backoff_ms'),
            'socket.timeout.ms'        => config('kafka.socket_timeout_ms'),
            'reconnect.backoff.max.ms' => config('kafka.reconnect_backoff_max_ms'),
            'request.timeout.ms'       => config('kafka.request_timeout_ms'),
        ]);
        // 设置 SASL 认证
        if ('SASL_SSL' == config('kafka.securityProtocol')) {
            $producer->withConfigOption(name: 'ssl.ca.location', option: __DIR__.'/only-4096-ca-cert');

            $producer->withSasl(new Sasl(
                username: config('kafka.sasl.username'),
                password: config('kafka.sasl.password'),
                mechanisms: config('kafka.sasl.mechanisms'),
                securityProtocol: config('kafka.securityProtocol'),
            ));
        }
        // 是否启用调试模式
        $producer->withDebugEnabled(config('kafka.debug'));
        // 设置序列化器
        $producer->usingSerializer(new Serializer());
        // 设置消息
        $producer->withMessage(new Message(
            topicName: $queue, // 设置 topic
            headers: [],
            body: $job, // 设置消息体
            key: null,
        ));
        // 发送消息
        $producer->send();
    }

    public function pushRaw($payload, $queue = null, array $options = [])
    {
    }

    public function later($delay, $job, $data = '', $queue = null)
    {
    }

    /**
     * @param  null|mixed                              $queue
     * @throws \Carbon\Exceptions\Exception|\Exception
     */
    public function pop($queue = null)
    {
        // 设置 消费者
        $consumer = Kafka::createConsumer();
        // 设置消费 topic
        $consumer->subscribe($queue);
        // 设置 DLQ（死信队列），不指定 DLQ topic 名称，将根据正在使用的 topic 名称添加 -dlq 后缀创建 DLQ topic 名称
        $consumer->withDlq();
        // 设置配置项
        $consumer->withOptions([
            'api.version.request'      => config('kafka.api_version_request'),
            'reconnect.backoff.max.ms' => config('kafka.reconnect_backoff_max_ms'),
            'session.timeout.ms'       => config('kafka.session_timeout_ms'),
        ]);
        // 设置 SASL 认证
        if ('SASL_SSL' == config('kafka.securityProtocol')) {
            $consumer->withOption(name: 'ssl.ca.location', value: __DIR__.'/only-4096-ca-cert');

            $consumer->withSasl(new Sasl(
                username: config('kafka.sasl.username'),
                password: config('kafka.sasl.password'),
                mechanisms: config('kafka.sasl.mechanisms'),
                securityProtocol: config('kafka.securityProtocol'),
            ));
        }
        // 设置序列化器
        $consumer->usingDeserializer(new Deserializer());
        // 设置处理程序
        $consumer->withHandler(function (KafkaConsumerMessage $message) {
            $message->getBody()->handle();
        });
        // 构建消费者
        $consumer = $consumer->build();
        // 设置消费者消费消息后的回调
        $consumer->onStopConsuming(static function () {
            Log::info('消费者监听已停止！！！');
        });
        // 消费消息
        $consumer->consume();
    }
}

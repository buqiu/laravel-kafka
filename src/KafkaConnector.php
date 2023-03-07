<?php

namespace Buqiu\Kafka;

use Illuminate\Queue\Connectors\ConnectorInterface;

class KafkaConnector implements ConnectorInterface
{
    public function connect(array $config): KafkaQueue
    {
        return new KafkaQueue($this->producer($config), $this->consumer($config), $config);
    }

    protected function producer(array $config): \RdKafka\Producer
    {
        // 通用配置
        $conf = $this->getConf($config);

        /**
         * Kafka producer 的 ack 有 3 种机制，分别说明如下：
         * -1 或 all：Broker 在 leader 收到数据并同步给所有 ISR 中的 follower 后，才应答给 Producer 继续发送下一条（批）消息。
         * 这种配置提供了最高的数据可靠性，只要有一个已同步的副本存活就不会有消息丢失。注意：这种配置不能确保所有的副本读写入该数据才返回，可以配合 Topic 级别参数 min.insync.replicas 使用。
         * 0：生产者不等待来自 broker 同步完成的确认，继续发送下一条（批）消息。这种配置生产性能最高，但数据可靠性最低（当服务器故障时可能会有数据丢失，如果 leader 已死但是 producer 不知情，则 broker 收不到消息）。
         * 1： 生产者在 leader 已成功收到的数据并得到确认后再发送下一条（批）消息。这种配置是在生产吞吐和数据可靠性之间的权衡（如果leader已死但是尚未复制，则消息可能丢失）。
         * 用户不显示配置时，默认值为 1，用户根据自己的业务情况进行设置。
         */
        $conf->set('acks', $config['acks']);
        // 请求发生错误时重试次数，建议将该值设置为大于0，失败重试最大程度保证消息不丢失
        $conf->set('retries', $config['retries']);
        // 发送请求失败时到下一次重试请求之间的时间
        $conf->set('retry.backoff.ms', $config['retry_backoff_ms']);
        // producer 网络请求的超时时间
        $conf->set('socket.timeout.ms', $config['socket_timeout_ms']);

        // 注册发送消息的回调
        if (!app()->isProduction()) {
            $conf->setDrMsgCb(function ($kafka, $message) {
                if ($message->err) {
                    printf("【Kafka Producer】 error: %s (errno: %d)\n", $message->errstr(), $message->err);
                } else {
                    echo '【Kafka Producer】send：message=' . var_export($message, true) . "\n";
                }
            });
        }
        // 注册发送消息错误的回调
        $conf->setErrorCb(function ($kafka, $err, $reason) {
            printf("【Kafka Producer】 error: %s (errno: %d)\n", $reason, $err);
        });

        return new \RdKafka\Producer($conf);
    }

    protected function consumer(array $config): \RdKafka\KafkaConsumer
    {
        // 通用配置
        $conf = $this->getConf($config);

        // 设置 组ID
        $conf->set('group.id', $config['group_id']);
        // 使用 Kafka 消费分组机制时，消费者超时时间，当 Broker 在该时间内没有收到消费者的心跳时，认为该消费者故障失败，Broker 发起重新 Rebalance 过程
        $conf->set('session.timeout.ms', $config['session_timeout_ms']);
        // 客户端请求超时时间，如果超过这个时间没有收到应答，则请求超时失败
        $conf->set('request.timeout.ms', $config['request_timeout_ms']);

        return new \RdKafka\KafkaConsumer($conf);
    }

    protected function getConf(array $config): \RdKafka\Conf
    {
        $conf = new \RdKafka\Conf();

        // ---------- 启用 SASL 验证时需要设置 start ----------
        // SASL 验证机制类型默认选用 PLAIN
        $conf->set('sasl.mechanisms', $config['sasl_mechanisms']);
        $conf->set('api.version.request', $config['api_version_request']);
        // 设置用户名：控制台 配置信息 的用户名
        $conf->set('sasl.username', $config['sasl_username']);
        // 设置密码：控制台 配置信息 的密码
        $conf->set('sasl.password', $config['sasl_password']);
        $conf->set('security.protocol', $config['security_protocol']);
        $conf->set('ssl.ca.location', __DIR__.'/only-4096-ca-cert');
        // ---------- 启用 SASL 验证时需要设置 end ----------

        // 设置客户端内部重试间隔
        $conf->set('reconnect.backoff.max.ms', $config['reconnect_backoff_max_ms']);
        // 设置入口服务，请通过控制台获取对应的服务地址
        $conf->set('metadata.broker.list', $config['bootstrap_servers']);


        return $conf;
    }
}

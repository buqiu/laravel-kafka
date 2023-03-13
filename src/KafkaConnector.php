<?php

namespace Buqiu\Kafka;

use Illuminate\Queue\Connectors\ConnectorInterface;

class KafkaConnector implements ConnectorInterface
{
    public function connect(array $config): KafkaQueue
    {
        return new KafkaQueue();
    }
}

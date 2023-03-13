<?php

namespace Buqiu\Kafka\Serializer;

use Junges\Kafka\Contracts\KafkaConsumerMessage;
use Junges\Kafka\Contracts\MessageDeserializer;
use Junges\Kafka\Message\ConsumedMessage;

class Deserializer implements MessageDeserializer
{
    /**
     * @param KafkaConsumerMessage $message
     * @return KafkaConsumerMessage
     */
    public function deserialize(KafkaConsumerMessage $message): KafkaConsumerMessage
    {
        $body = unserialize($message->getBody());

        return new ConsumedMessage(
            topicName: $message->getTopicName(),
            partition: $message->getPartition(),
            headers: $message->getHeaders(),
            body: $body,
            key: $message->getKey(),
            offset: $message->getOffset(),
            timestamp: $message->getTimestamp()
        );
    }
}

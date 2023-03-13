<?php

namespace Buqiu\Kafka\Serializer;

use Junges\Kafka\Contracts\KafkaProducerMessage;
use Junges\Kafka\Contracts\MessageSerializer;

class Serializer implements MessageSerializer
{
    /**
     * @param KafkaProducerMessage $message
     * @return KafkaProducerMessage
     */
    public function serialize(KafkaProducerMessage $message): KafkaProducerMessage
    {
        $body = serialize($message->getBody());

        return $message->withBody($body);
    }
}

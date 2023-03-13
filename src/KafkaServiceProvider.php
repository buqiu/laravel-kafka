<?php

namespace Buqiu\Kafka;

use Illuminate\Support\ServiceProvider;

class KafkaServiceProvider extends ServiceProvider
{
    public function boot()
    {
        $this->publishesConfiguration();

        $manager = $this->app['queue'];

        $manager->addConnector('kafka', fn() => new KafkaConnector);
    }

    private function publishesConfiguration()
    {
        $this->publishes([
            __DIR__."/../config/kafka.php" => config_path('kafka.php'),
        ], 'buqiu-kafka-config');
    }
}

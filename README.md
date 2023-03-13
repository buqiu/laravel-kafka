# kafka

## 介绍
Kafka 分布式发布订阅消息系统扩展包。

## 环境
```
php >= 8.1
composer >= 2.0
```

## 扩展
```
RdKafka 自行安装php扩展
```

## 使用
### 1.安装
```shell
composer require buqiu/kafka
```
### 2.发布 **_kafka.php_** 配置文件
```shell
php artisan vendor:publish --tag=buqiu-kafka-config
```
### 3.修改 config/queue.php 文件，在 **_connections_** 数组中追加 **_kafka_** 配置信息
```php
'kafka' => [
            'driver' => 'kafka',
            'queue' => env('KAFKA_TOPIC'),
        ],
```
### 4.配置 .env 文件
```dotenv
KAFKA_BROKERS=服务地址
KAFKA_SECURITY_PROTOCOL=SASL_SSL
KAFKA_SASL_MECHANISMS=PLAIN
KAFKA_SASL_USERNAME=用户名
KAFKA_SASL_PASSWORD=密码
KAFKA_DEBUG=false
KAFKA_TOPIC=自定义的 Topic
KAFKA_CONSUMER_GROUP_ID=自定义的 Group ID
```
### 5.启动 kafka 队列监听
```php
php artisan queue:work kafka
```
### 6.代码示例
#### 6.1 修改 .env 文件设置默认队列连接
.env
```dotenv
QUEUE_CONNECTION=kafka
```
php 代码中使用
```php
KafkaJob::dispatch()->onQueue('default');
```
#### 6.2 不修改 .env 默认队列连接，手动指定连接
方式一：调用处动态指定
```php
KafkaJob::dispatch()->onQueue('default')->onConnection('kafka');
```
方式二、KafkaJob 中指定连接属性（推荐）
```php
public function __construct()
{
    $this->onConnection('kafka');
}
```
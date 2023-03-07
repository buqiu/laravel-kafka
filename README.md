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

### 2.修改 config/queue.php 文件，在 **_connections_** 数组中追加 **_kafka_** 配置信息

```php
'kafka' => [
            'driver' => 'kafka',
            'queue' => env('KAFKA_QUEUE', 'default'),
            'bootstrap_servers' => env('BOOTSTRAP_SERVERS'),
            'security_protocol' => env('SECURITY_PROTOCOL', 'SASL_SSL'),
            'sasl_mechanisms' => env('SASL_MECHANISMS', 'PLAIN'),
            'sasl_username' => env('SASL_USERNAME'),
            'sasl_password' => env('SASL_PASSWORD'),
            'group_id' => env('GROUP_ID', 'default'),
            'acks' => env('ACKS', 1),
            'retries' => env('RETRIES', 3),
            'retry_backoff_ms' => env('RETRY_BACKOFF_MS', 100),
            'socket_timeout_ms' => env('SOCKET_TIMEOUT_MS', 6000),
            'reconnect_backoff_max_ms' => env('RECONNECT_BACKOFF_MAX_MS', 3000),
            'session_timeout_ms' => env('SESSION_TIMEOUT_MS', 10000),
            'request_timeout_ms' => env('REQUEST_TIMEOUT_MS', 305000),
            'consume_max_ms' => env('CONSUME_MAX_MS', 10000),
            'flush_max_ms' => env('FLUSH_MAX_MS', 1000),
            'api_version_request' => env('API_VERSION_REQUEST', true),
        ],
```

### 3.配置 .env 文件

```dotenv
KAFKA_QUEUE=自定义的 Topic
BOOTSTRAP_SERVERS=服务地址
SECURITY_PROTOCOL=SASL_SSL
SASL_MECHANISMS=PLAIN
SASL_USERNAME=用户名
SASL_PASSWORD=密码
GROUP_ID=自定义的 Group ID
```

### 4.启动 kafka 队列监听

```php
php artisan queue:work kafka
```

### 5.代码示例

#### 5.1 修改 .env 文件设置默认队列连接

.env

```dotenv
QUEUE_CONNECTION=kafka
```

php 代码中使用

```php
KafkaJob::dispatch()->onQueue('default');
```

#### 5.2 不修改 .env 默认队列连接，手动指定连接

方式一：调用处动态指定

```php
KafkaJob::dispatch()->onQueue('default')->onConnection('kafka');
```

方式二、KafkaJob 中指定连接属性（推荐）

```php
public $connection = 'kafka';
```
<?php
/**
 * 队列，Redis连接器
 */

namespace Illuminate\Queue\Connectors;

use Illuminate\Contracts\Redis\Factory as Redis;
use Illuminate\Queue\RedisQueue;

class RedisConnector implements ConnectorInterface
{
    /**
     * The Redis database instance.
	 * Redis数据库实例
     *
     * @var \Illuminate\Contracts\Redis\Factory
     */
    protected $redis;

    /**
     * The connection name.
	 * 连接名
     *
     * @var string
     */
    protected $connection;

    /**
     * Create a new Redis queue connector instance.
	 * 创建新的Redis队列连接实例
     *
     * @param  \Illuminate\Contracts\Redis\Factory  $redis
     * @param  string|null  $connection
     * @return void
     */
    public function __construct(Redis $redis, $connection = null)
    {
        $this->redis = $redis;
        $this->connection = $connection;
    }

    /**
     * Establish a queue connection.
	 * 建立队列连接
     *
     * @param  array  $config
     * @return \Illuminate\Contracts\Queue\Queue
     */
    public function connect(array $config)
    {
        return new RedisQueue(
            $this->redis, $config['queue'],
            $config['connection'] ?? $this->connection,
            $config['retry_after'] ?? 60,
            $config['block_for'] ?? null
        );
    }
}

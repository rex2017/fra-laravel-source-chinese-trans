<?php
/**
 * Redis管理，核心类
 */

namespace Illuminate\Redis;

use Closure;
use Illuminate\Contracts\Redis\Factory;
use Illuminate\Redis\Connections\Connection;
use Illuminate\Redis\Connectors\PhpRedisConnector;
use Illuminate\Redis\Connectors\PredisConnector;
use Illuminate\Support\ConfigurationUrlParser;
use InvalidArgumentException;

/**
 * @mixin \Illuminate\Redis\Connections\Connection
 */
class RedisManager implements Factory
{
    /**
     * The application instance.
	 * 应用实例
     *
     * @var \Illuminate\Contracts\Foundation\Application
     */
    protected $app;

    /**
     * The name of the default driver.
	 * 默认驱动名称
     *
     * @var string
     */
    protected $driver;

    /**
     * The registered custom driver creators.
	 * 自定义驱动创建者
     *
     * @var array
     */
    protected $customCreators = [];

    /**
     * The Redis server configurations.
	 * Redis服务配置
     *
     * @var array
     */
    protected $config;

    /**
     * The Redis connections.
	 * Redis连接
     *
     * @var mixed
     */
    protected $connections;

    /**
     * Indicates whether event dispatcher is set on connections.
	 * 指明是否在连接上设置事件调度程序
     *
     * @var bool
     */
    protected $events = false;

    /**
     * Create a new Redis manager instance.
	 * 创建新的Redis管理实例
     *
     * @param  \Illuminate\Contracts\Foundation\Application  $app
     * @param  string  $driver
     * @param  array  $config
     * @return void
     */
    public function __construct($app, $driver, array $config)
    {
        $this->app = $app;
        $this->driver = $driver;
        $this->config = $config;
    }

    /**
     * Get a Redis connection by name.
	 * 得到Redis连接通过名称
     *
     * @param  string|null  $name
     * @return \Illuminate\Redis\Connections\Connection
     */
    public function connection($name = null)
    {
        $name = $name ?: 'default';

        if (isset($this->connections[$name])) {
            return $this->connections[$name];
        }

        return $this->connections[$name] = $this->configure(
            $this->resolve($name), $name
        );
    }

    /**
     * Resolve the given connection by name.
	 * 解析给定的连接按名称
     *
     * @param  string|null  $name
     * @return \Illuminate\Redis\Connections\Connection
     *
     * @throws \InvalidArgumentException
     */
    public function resolve($name = null)
    {
        $name = $name ?: 'default';

        $options = $this->config['options'] ?? [];

        if (isset($this->config[$name])) {
            return $this->connector()->connect(
                $this->parseConnectionConfiguration($this->config[$name]),
                $options
            );
        }

        if (isset($this->config['clusters'][$name])) {
            return $this->resolveCluster($name);
        }

        throw new InvalidArgumentException("Redis connection [{$name}] not configured.");
    }

    /**
     * Resolve the given cluster connection by name.
	 * 解析给定的集群连接按名称
     *
     * @param  string  $name
     * @return \Illuminate\Redis\Connections\Connection
     */
    protected function resolveCluster($name)
    {
        return $this->connector()->connectToCluster(
            array_map(function ($config) {
                return $this->parseConnectionConfiguration($config);
            }, $this->config['clusters'][$name]),
            $this->config['clusters']['options'] ?? [],
            $this->config['options'] ?? []
        );
    }

    /**
     * Configure the given connection to prepare it for commands.
	 * 配置给定的连接以便为命令做好准备
     *
     * @param  \Illuminate\Redis\Connections\Connection  $connection
     * @param  string  $name
     * @return \Illuminate\Redis\Connections\Connection
     */
    protected function configure(Connection $connection, $name)
    {
        $connection->setName($name);

        if ($this->events && $this->app->bound('events')) {
            $connection->setEventDispatcher($this->app->make('events'));
        }

        return $connection;
    }

    /**
     * Get the connector instance for the current driver.
	 * 得到当前驱动程序的连接器实例
     *
     * @return \Illuminate\Contracts\Redis\Connector
     */
    protected function connector()
    {
        $customCreator = $this->customCreators[$this->driver] ?? null;

        if ($customCreator) {
            return $customCreator();
        }

        switch ($this->driver) {
            case 'predis':
                return new PredisConnector;
            case 'phpredis':
                return new PhpRedisConnector;
        }
    }

    /**
     * Parse the Redis connection configuration.
	 * 解析Redis连接配置
     *
     * @param  mixed  $config
     * @return array
     */
    protected function parseConnectionConfiguration($config)
    {
        $parsed = (new ConfigurationUrlParser)->parseConfiguration($config);

        $driver = strtolower($parsed['driver'] ?? '');

        if (in_array($driver, ['tcp', 'tls'])) {
            $parsed['scheme'] = $driver;
        }

        return array_filter($parsed, function ($key) {
            return ! in_array($key, ['driver'], true);
        }, ARRAY_FILTER_USE_KEY);
    }

    /**
     * Return all of the created connections.
	 * 返回所有已创建连接
     *
     * @return array
     */
    public function connections()
    {
        return $this->connections;
    }

    /**
     * Enable the firing of Redis command events.
	 * 启用Redis命令事件的触发
     *
     * @return void
     */
    public function enableEvents()
    {
        $this->events = true;
    }

    /**
     * Disable the firing of Redis command events.
	 * 禁用Redis命令事件的触发
     *
     * @return void
     */
    public function disableEvents()
    {
        $this->events = false;
    }

    /**
     * Set the default driver.
	 * 设置默认驱动
     *
     * @param  string  $driver
     * @return void
     */
    public function setDriver($driver)
    {
        $this->driver = $driver;
    }

    /**
     * Register a custom driver creator Closure.
	 * 注册自定义驱动程序创建器Closure
     *
     * @param  string  $driver
     * @param  \Closure  $callback
     * @return $this
     */
    public function extend($driver, Closure $callback)
    {
        $this->customCreators[$driver] = $callback->bindTo($this, $this);

        return $this;
    }

    /**
     * Pass methods onto the default Redis connection.
	 * 传递方法到默认的Redis连接
     *
     * @param  string  $method
     * @param  array  $parameters
     * @return mixed
     */
    public function __call($method, $parameters)
    {
        return $this->connection()->{$method}(...$parameters);
    }
}

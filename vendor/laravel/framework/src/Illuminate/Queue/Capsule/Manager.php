<?php
/**
 * 队列，压缩管理
 */

namespace Illuminate\Queue\Capsule;

use Illuminate\Container\Container;
use Illuminate\Queue\QueueManager;
use Illuminate\Queue\QueueServiceProvider;
use Illuminate\Support\Traits\CapsuleManagerTrait;

/**
 * @mixin \Illuminate\Queue\QueueManager
 * @mixin \Illuminate\Contracts\Queue\Queue
 */
class Manager
{
    use CapsuleManagerTrait;

    /**
     * The queue manager instance.
	 * 队列管理实例
     *
     * @var \Illuminate\Queue\QueueManager
     */
    protected $manager;

    /**
     * Create a new queue capsule manager.
	 * 创建新的队列压缩管理
     *
     * @param  \Illuminate\Container\Container|null  $container
     * @return void
     */
    public function __construct(Container $container = null)
    {
        $this->setupContainer($container ?: new Container);

        // Once we have the container setup, we will setup the default configuration
        // options in the container "config" bindings. This just makes this queue
        // manager behave correctly since all the correct binding are in place.
		// 一旦我们完成了容器设置，我们将在容器"config"绑定中设置默认配置选项。
		// 这只会使此队列管理器正确运行，因为所有正确的绑定都已到位。
        $this->setupDefaultConfiguration();

        $this->setupManager();

        $this->registerConnectors();
    }

    /**
     * Setup the default queue configuration options.
	 * 设置默认队列配置选项
     *
     * @return void
     */
    protected function setupDefaultConfiguration()
    {
        $this->container['config']['queue.default'] = 'default';
    }

    /**
     * Build the queue manager instance.
	 * 构建队列管理实例
     *
     * @return void
     */
    protected function setupManager()
    {
        $this->manager = new QueueManager($this->container);
    }

    /**
     * Register the default connectors that the component ships with.
	 * 注册组件附带的默认连接器
     *
     * @return void
     */
    protected function registerConnectors()
    {
        $provider = new QueueServiceProvider($this->container);

        $provider->registerConnectors($this->manager);
    }

    /**
     * Get a connection instance from the global manager.
	 * 得到连接实例从全局管理器
     *
     * @param  string|null  $connection
     * @return \Illuminate\Contracts\Queue\Queue
     */
    public static function connection($connection = null)
    {
        return static::$instance->getConnection($connection);
    }

    /**
     * Push a new job onto the queue.
	 * 推送新作业到队列中
     *
     * @param  string  $job
     * @param  mixed  $data
     * @param  string|null  $queue
     * @param  string|null  $connection
     * @return mixed
     */
    public static function push($job, $data = '', $queue = null, $connection = null)
    {
        return static::$instance->connection($connection)->push($job, $data, $queue);
    }

    /**
     * Push a new an array of jobs onto the queue.
	 * 推送新作业数组到队列中
     *
     * @param  array  $jobs
     * @param  mixed  $data
     * @param  string|null  $queue
     * @param  string|null  $connection
     * @return mixed
     */
    public static function bulk($jobs, $data = '', $queue = null, $connection = null)
    {
        return static::$instance->connection($connection)->bulk($jobs, $data, $queue);
    }

    /**
     * Push a new job onto the queue after a delay.
	 * 推送新作业到队列在延迟
     *
     * @param  \DateTimeInterface|\DateInterval|int  $delay
     * @param  string  $job
     * @param  mixed  $data
     * @param  string|null  $queue
     * @param  string|null  $connection
     * @return mixed
     */
    public static function later($delay, $job, $data = '', $queue = null, $connection = null)
    {
        return static::$instance->connection($connection)->later($delay, $job, $data, $queue);
    }

    /**
     * Get a registered connection instance.
	 * 得到已注册的连接实例
     *
     * @param  string|null  $name
     * @return \Illuminate\Contracts\Queue\Queue
     */
    public function getConnection($name = null)
    {
        return $this->manager->connection($name);
    }

    /**
     * Register a connection with the manager.
	 * 注册与管理器的连接
     *
     * @param  array  $config
     * @param  string  $name
     * @return void
     */
    public function addConnection(array $config, $name = 'default')
    {
        $this->container['config']["queue.connections.{$name}"] = $config;
    }

    /**
     * Get the queue manager instance.
	 * 得到队列管理器实例
     *
     * @return \Illuminate\Queue\QueueManager
     */
    public function getQueueManager()
    {
        return $this->manager;
    }

    /**
     * Pass dynamic instance methods to the manager.
	 * 传递动态实例方法给管理器
     *
     * @param  string  $method
     * @param  array  $parameters
     * @return mixed
     */
    public function __call($method, $parameters)
    {
        return $this->manager->$method(...$parameters);
    }

    /**
     * Dynamically pass methods to the default connection.
	 * 动态地传递方法给默认连接
     *
     * @param  string  $method
     * @param  array  $parameters
     * @return mixed
     */
    public static function __callStatic($method, $parameters)
    {
        return static::connection()->$method(...$parameters);
    }
}

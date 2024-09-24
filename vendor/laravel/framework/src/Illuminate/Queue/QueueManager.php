<?php
/**
 * 队列管理，队列核心类
 */

namespace Illuminate\Queue;

use Closure;
use Illuminate\Contracts\Queue\Factory as FactoryContract;
use Illuminate\Contracts\Queue\Monitor as MonitorContract;
use InvalidArgumentException;

/**
 * @mixin \Illuminate\Contracts\Queue\Queue
 */
class QueueManager implements FactoryContract, MonitorContract
{
    /**
     * The application instance.
	 * 应用实例
     *
     * @var \Illuminate\Contracts\Foundation\Application
     */
    protected $app;

    /**
     * The array of resolved queue connections.
	 * 已解析队列连接数组
     *
     * @var array
     */
    protected $connections = [];

    /**
     * The array of resolved queue connectors.
	 * 已解析队列连接器数组
     *
     * @var array
     */
    protected $connectors = [];

    /**
     * Create a new queue manager instance.
	 * 创建新的队列管理实例
     *
     * @param  \Illuminate\Contracts\Foundation\Application  $app
     * @return void
     */
    public function __construct($app)
    {
        $this->app = $app;
    }

    /**
     * Register an event listener for the before job event.
	 * 注册一个事件侦听器为before作业事件
     *
     * @param  mixed  $callback
     * @return void
     */
    public function before($callback)
    {
        $this->app['events']->listen(Events\JobProcessing::class, $callback);
    }

    /**
     * Register an event listener for the after job event.
	 * 注册一个事件侦听器为after作业事件
     *
     * @param  mixed  $callback
     * @return void
     */
    public function after($callback)
    {
        $this->app['events']->listen(Events\JobProcessed::class, $callback);
    }

    /**
     * Register an event listener for the exception occurred job event.
	 * 注册事件侦听器为异常发生的作业事件
     *
     * @param  mixed  $callback
     * @return void
     */
    public function exceptionOccurred($callback)
    {
        $this->app['events']->listen(Events\JobExceptionOccurred::class, $callback);
    }

    /**
     * Register an event listener for the daemon queue loop.
	 * 注册一个事件侦听器为守护进程队列循环
     *
     * @param  mixed  $callback
     * @return void
     */
    public function looping($callback)
    {
        $this->app['events']->listen(Events\Looping::class, $callback);
    }

    /**
     * Register an event listener for the failed job event.
	 * 注册一个事件侦听器为失败的作业事件
     *
     * @param  mixed  $callback
     * @return void
     */
    public function failing($callback)
    {
        $this->app['events']->listen(Events\JobFailed::class, $callback);
    }

    /**
     * Register an event listener for the daemon queue stopping.
	 * 注册一个事件侦听器为守护进程队列停止
     *
     * @param  mixed  $callback
     * @return void
     */
    public function stopping($callback)
    {
        $this->app['events']->listen(Events\WorkerStopping::class, $callback);
    }

    /**
     * Determine if the driver is connected.
	 * 确定驱动程序是否已连接
     *
     * @param  string|null  $name
     * @return bool
     */
    public function connected($name = null)
    {
        return isset($this->connections[$name ?: $this->getDefaultDriver()]);
    }

    /**
     * Resolve a queue connection instance.
	 * 解析队列连接实例
     *
     * @param  string|null  $name
     * @return \Illuminate\Contracts\Queue\Queue
     */
    public function connection($name = null)
    {
        $name = $name ?: $this->getDefaultDriver();

        // If the connection has not been resolved yet we will resolve it now as all
        // of the connections are resolved when they are actually needed so we do
        // not make any unnecessary connection to the various queue end-points.
		// 如果连接尚未解析，我们现在将解析它，因为所有连接都是在实际需要时解析的，
		// 所以我们不会对各个队列端点进行任何不必要的连接。
        if (! isset($this->connections[$name])) {
            $this->connections[$name] = $this->resolve($name);

            $this->connections[$name]->setContainer($this->app);
        }

        return $this->connections[$name];
    }

    /**
     * Resolve a queue connection.
	 * 解析队列连接
     *
     * @param  string  $name
     * @return \Illuminate\Contracts\Queue\Queue
     */
    protected function resolve($name)
    {
        $config = $this->getConfig($name);

        return $this->getConnector($config['driver'])
                        ->connect($config)
                        ->setConnectionName($name);
    }

    /**
     * Get the connector for a given driver.
	 * 得到给定驱动程序的连接器
     *
     * @param  string  $driver
     * @return \Illuminate\Queue\Connectors\ConnectorInterface
     *
     * @throws \InvalidArgumentException
     */
    protected function getConnector($driver)
    {
        if (! isset($this->connectors[$driver])) {
            throw new InvalidArgumentException("No connector for [$driver]");
        }

        return call_user_func($this->connectors[$driver]);
    }

    /**
     * Add a queue connection resolver.
	 * 添加队列连接解析器
     *
     * @param  string  $driver
     * @param  \Closure  $resolver
     * @return void
     */
    public function extend($driver, Closure $resolver)
    {
        return $this->addConnector($driver, $resolver);
    }

    /**
     * Add a queue connection resolver.
	 * 添加队列连接解析器
     *
     * @param  string  $driver
     * @param  \Closure  $resolver
     * @return void
     */
    public function addConnector($driver, Closure $resolver)
    {
        $this->connectors[$driver] = $resolver;
    }

    /**
     * Get the queue connection configuration.
	 * 得到队列连接配置
     *
     * @param  string  $name
     * @return array
     */
    protected function getConfig($name)
    {
        if (! is_null($name) && $name !== 'null') {
            return $this->app['config']["queue.connections.{$name}"];
        }

        return ['driver' => 'null'];
    }

    /**
     * Get the name of the default queue connection.
	 * 得到默认队列连接的名称
     *
     * @return string
     */
    public function getDefaultDriver()
    {
        return $this->app['config']['queue.default'];
    }

    /**
     * Set the name of the default queue connection.
	 * 设置默认队列连接的名称
     *
     * @param  string  $name
     * @return void
     */
    public function setDefaultDriver($name)
    {
        $this->app['config']['queue.default'] = $name;
    }

    /**
     * Get the full name for the given connection.
	 * 得到给定连接的全名
     *
     * @param  string|null  $connection
     * @return string
     */
    public function getName($connection = null)
    {
        return $connection ?: $this->getDefaultDriver();
    }

    /**
     * Dynamically pass calls to the default connection.
	 * 动态地将调用传递给默认连接
     *
     * @param  string  $method
     * @param  array  $parameters
     * @return mixed
     */
    public function __call($method, $parameters)
    {
        return $this->connection()->$method(...$parameters);
    }
}

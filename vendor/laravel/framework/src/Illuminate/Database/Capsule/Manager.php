<?php
/**
 * 数据库，压缩管理
 */

namespace Illuminate\Database\Capsule;

use Illuminate\Container\Container;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Database\Connectors\ConnectionFactory;
use Illuminate\Database\DatabaseManager;
use Illuminate\Database\Eloquent\Model as Eloquent;
use Illuminate\Support\Traits\CapsuleManagerTrait;
use PDO;

class Manager
{
    use CapsuleManagerTrait;

    /**
     * The database manager instance.
	 * 数据库管理器实例
     *
     * @var \Illuminate\Database\DatabaseManager
     */
    protected $manager;

    /**
     * Create a new database capsule manager.
	 * 创建新的数据库压缩管理器
     *
     * @param  \Illuminate\Container\Container|null  $container
     * @return void
     */
    public function __construct(Container $container = null)
    {
        $this->setupContainer($container ?: new Container);

        // Once we have the container setup, we will setup the default configuration
        // options in the container "config" binding. This will make the database
        // manager work correctly out of the box without extreme configuration.
		// 如果我们无法解析实例，我们将检查值是否是可选的。
		// 如果是，我们将返回可选参数值为依赖关系的值，类似于我们如何使用标量。
        $this->setupDefaultConfiguration();

        $this->setupManager();
    }

    /**
     * Setup the default database configuration options.
	 * 设置默认的数据库配置选项
     *
     * @return void
     */
    protected function setupDefaultConfiguration()
    {
        $this->container['config']['database.fetch'] = PDO::FETCH_OBJ;

        $this->container['config']['database.default'] = 'default';
    }

    /**
     * Build the database manager instance.
	 * 构建数据库管理实例
     *
     * @return void
     */
    protected function setupManager()
    {
        $factory = new ConnectionFactory($this->container);

        $this->manager = new DatabaseManager($this->container, $factory);
    }

    /**
     * Get a connection instance from the global manager.
	 * 得到连接实例从全局管理器
     *
     * @param  string|null  $connection
     * @return \Illuminate\Database\Connection
     */
    public static function connection($connection = null)
    {
        return static::$instance->getConnection($connection);
    }

    /**
     * Get a fluent query builder instance.
	 * 获取一个流畅的查询生成器实例
     *
     * @param  \Closure|\Illuminate\Database\Query\Builder|string  $table
     * @param  string|null  $as
     * @param  string|null  $connection
     * @return \Illuminate\Database\Query\Builder
     */
    public static function table($table, $as = null, $connection = null)
    {
        return static::$instance->connection($connection)->table($table, $as);
    }

    /**
     * Get a schema builder instance.
	 * 得到模式构建器实例
     *
     * @param  string|null  $connection
     * @return \Illuminate\Database\Schema\Builder
     */
    public static function schema($connection = null)
    {
        return static::$instance->connection($connection)->getSchemaBuilder();
    }

    /**
     * Get a registered connection instance.
	 * 得到已注册的连接实例
     *
     * @param  string|null  $name
     * @return \Illuminate\Database\Connection
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
        $connections = $this->container['config']['database.connections'];

        $connections[$name] = $config;

        $this->container['config']['database.connections'] = $connections;
    }

    /**
     * Bootstrap Eloquent so it is ready for usage.
	 * 引导Eloquen，它将准备使用。
     *
     * @return void
     */
    public function bootEloquent()
    {
        Eloquent::setConnectionResolver($this->manager);

        // If we have an event dispatcher instance, we will go ahead and register it
        // with the Eloquent ORM, allowing for model callbacks while creating and
        // updating "model" instances; however, it is not necessary to operate.
		// 如果我们有一个事件调度器实例，我们将继续注册它使用Eloquent ORM,
		// 允许在创建和更新"模型"实例，但是不需要操作。
        if ($dispatcher = $this->getEventDispatcher()) {
            Eloquent::setEventDispatcher($dispatcher);
        }
    }

    /**
     * Set the fetch mode for the database connections.
	 * 设置数据库连接的获取模式
     *
     * @param  int  $fetchMode
     * @return $this
     */
    public function setFetchMode($fetchMode)
    {
        $this->container['config']['database.fetch'] = $fetchMode;

        return $this;
    }

    /**
     * Get the database manager instance.
	 * 得到数据库管理器实例
     *
     * @return \Illuminate\Database\DatabaseManager
     */
    public function getDatabaseManager()
    {
        return $this->manager;
    }

    /**
     * Get the current event dispatcher instance.
	 * 得到当前事件调度程序实例
     *
     * @return \Illuminate\Contracts\Events\Dispatcher|null
     */
    public function getEventDispatcher()
    {
        if ($this->container->bound('events')) {
            return $this->container['events'];
        }
    }

    /**
     * Set the event dispatcher instance to be used by connections.
	 * 设置要由连接使用的事件调度程序实例
     *
     * @param  \Illuminate\Contracts\Events\Dispatcher  $dispatcher
     * @return void
     */
    public function setEventDispatcher(Dispatcher $dispatcher)
    {
        $this->container->instance('events', $dispatcher);
    }

    /**
     * Dynamically pass methods to the default connection.
	 * 动态地将方法传递给默认连接
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

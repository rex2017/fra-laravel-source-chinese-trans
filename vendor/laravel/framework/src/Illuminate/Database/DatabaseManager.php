<?php
/**
 * 数据库管理器，与db门面对应
 */

namespace Illuminate\Database;

use Illuminate\Database\Connectors\ConnectionFactory;
use Illuminate\Support\Arr;
use Illuminate\Support\ConfigurationUrlParser;
use Illuminate\Support\Str;
use InvalidArgumentException;
use PDO;

/**
 * @mixin \Illuminate\Database\Connection
 */
class DatabaseManager implements ConnectionResolverInterface
{
    /**
     * The application instance.
	 * 应用实例
     *
     * @var \Illuminate\Contracts\Foundation\Application
     */
    protected $app;

    /**
     * The database connection factory instance.
	 * 数据库连接工厂实例
     *
     * @var \Illuminate\Database\Connectors\ConnectionFactory
     */
    protected $factory;

    /**
     * The active connection instances.
	 * 活动连接实例
     *
     * @var array
     */
    protected $connections = [];

    /**
     * The custom connection resolvers.
	 * 自定义连接
     *
     * @var array
     */
    protected $extensions = [];

    /**
     * The callback to be executed to reconnect to a database.
	 * 为重新连接数据库而执行的回调
     *
     * @var callable
     */
    protected $reconnector;

    /**
     * Create a new database manager instance.
	 * 创建新的数据库管理实例
     *
     * @param  \Illuminate\Contracts\Foundation\Application  $app
     * @param  \Illuminate\Database\Connectors\ConnectionFactory  $factory
     * @return void
     */
    public function __construct($app, ConnectionFactory $factory)
    {
        $this->app = $app;
        $this->factory = $factory;

        $this->reconnector = function ($connection) {
            $this->reconnect($connection->getName());
        };
    }

    /**
     * Get a database connection instance.
	 * 得到数据库连接实例
     *
     * @param  string|null  $name
     * @return \Illuminate\Database\Connection
     */
    public function connection($name = null)
    {
        [$database, $type] = $this->parseConnectionName($name);

        $name = $name ?: $database;

        // If we haven't created this connection, we'll create it based on the config
        // provided in the application. Once we've created the connections we will
        // set the "fetch mode" for PDO which determines the query return types.
		// 如果我们还没有创建些连接，我们将根据配置创建它，
		// 一旦我们创建了连接，我们将为PDO设置获取模式，该模式决定了查询返回类型。
        if (! isset($this->connections[$name])) {
            $this->connections[$name] = $this->configure(
                $this->makeConnection($database), $type
            );
        }

        return $this->connections[$name];
    }

    /**
     * Parse the connection into an array of the name and read / write type.
	 * 将连接解析为名称和读/写类型的数组
     *
     * @param  string  $name
     * @return array
     */
    protected function parseConnectionName($name)
    {
        $name = $name ?: $this->getDefaultConnection();

        return Str::endsWith($name, ['::read', '::write'])
                            ? explode('::', $name, 2) : [$name, null];
    }

    /**
     * Make the database connection instance.
	 * 创建数据库连接实例
     *
     * @param  string  $name
     * @return \Illuminate\Database\Connection
     */
    protected function makeConnection($name)
    {
        $config = $this->configuration($name);

        // First we will check by the connection name to see if an extension has been
        // registered specifically for that connection. If it has we will call the
        // Closure and pass it the config allowing it to resolve the connection.
		// 首先我们先检查连接名看看专门为连接的扩展是否注册
		// 如果已经存在我们将调取配置并允许去解析连接
        if (isset($this->extensions[$name])) {
            return call_user_func($this->extensions[$name], $config, $name);
        }

        // Next we will check to see if an extension has been registered for a driver
        // and will call the Closure if so, which allows us to have a more generic
        // resolver for the drivers themselves which applies to all connections.
		// 接下来我们将检查是否已经为驱动注册了扩展名
		// 我们将调用闭包，这样我们就可以有一个更通用的闭包适用于所有连接的驱动程序解析器
        if (isset($this->extensions[$driver = $config['driver']])) {
            return call_user_func($this->extensions[$driver], $config, $name);
        }

        return $this->factory->make($config, $name);
    }

    /**
     * Get the configuration for a connection.
	 * 得到连接配置
     *
     * @param  string  $name
     * @return array
     *
     * @throws \InvalidArgumentException
     */
    protected function configuration($name)
    {
        $name = $name ?: $this->getDefaultConnection();

        // To get the database connection configuration, we will just pull each of the
        // connection configurations and get the configurations for the given name.
        // If the configuration doesn't exist, we'll throw an exception and bail.
		// 要得到数据库连接配置，我们只需拉取每个连接配置，并得到配置项
		// 如果配置不存在，我们将抛出异常
        $connections = $this->app['config']['database.connections'];

        if (is_null($config = Arr::get($connections, $name))) {
            throw new InvalidArgumentException("Database connection [{$name}] not configured.");
        }

        return (new ConfigurationUrlParser)
                    ->parseConfiguration($config);
    }

    /**
     * Prepare the database connection instance.
	 * 准备数据库连接实例
     *
     * @param  \Illuminate\Database\Connection  $connection
     * @param  string  $type
     * @return \Illuminate\Database\Connection
     */
    protected function configure(Connection $connection, $type)
    {
        $connection = $this->setPdoForType($connection, $type);

        // First we'll set the fetch mode and a few other dependencies of the database
        // connection. This method basically just configures and prepares it to get
        // used by the application. Once we're finished we'll return it back out.
		// 首先，我们将设置获取模式和数据库的其他一些连接依赖关系。
		// 此方法基本上只是配置和准备它以获得由应用使用。
		// 一旦完成将会释放。
        if ($this->app->bound('events')) {
            $connection->setEventDispatcher($this->app['events']);
        }

        // Here we'll set a reconnector callback. This reconnector can be any callable
        // so we will set a Closure to reconnect from this manager with the name of
        // the connection, which will allow us to reconnect from the connections.
		// 在这里，我们将设置一个重新连接回调。此重新连接器可以是任何可调用的，因此我们将设置一个闭包，
		// 以使用连接的名称从此管理器重新连接，这将允许我们从连接重新连接。
        $connection->setReconnector($this->reconnector);

        return $connection;
    }

    /**
     * Prepare the read / write mode for database connection instance.
	 * 准备读写模式为数据库连接实例
     *
     * @param  \Illuminate\Database\Connection  $connection
     * @param  string|null  $type
     * @return \Illuminate\Database\Connection
     */
    protected function setPdoForType(Connection $connection, $type = null)
    {
        if ($type === 'read') {
            $connection->setPdo($connection->getReadPdo());
        } elseif ($type === 'write') {
            $connection->setReadPdo($connection->getPdo());
        }

        return $connection;
    }

    /**
     * Disconnect from the given database and remove from local cache.
	 * 断开与给定数据库的连接
     *
     * @param  string|null  $name
     * @return void
     */
    public function purge($name = null)
    {
        $name = $name ?: $this->getDefaultConnection();

        $this->disconnect($name);

        unset($this->connections[$name]);
    }

    /**
     * Disconnect from the given database.
	 * 断开连接从数据库
     *
     * @param  string|null  $name
     * @return void
     */
    public function disconnect($name = null)
    {
        if (isset($this->connections[$name = $name ?: $this->getDefaultConnection()])) {
            $this->connections[$name]->disconnect();
        }
    }

    /**
     * Reconnect to the given database.
	 * 重新连接数据库
     *
     * @param  string|null  $name
     * @return \Illuminate\Database\Connection
     */
    public function reconnect($name = null)
    {
        $this->disconnect($name = $name ?: $this->getDefaultConnection());

        if (! isset($this->connections[$name])) {
            return $this->connection($name);
        }

        return $this->refreshPdoConnections($name);
    }

    /**
     * Refresh the PDO connections on a given connection.
	 * 刷新给定连接上的PDO连接
     *
     * @param  string  $name
     * @return \Illuminate\Database\Connection
     */
    protected function refreshPdoConnections($name)
    {
        $fresh = $this->makeConnection($name);

        return $this->connections[$name]
                                ->setPdo($fresh->getRawPdo())
                                ->setReadPdo($fresh->getRawReadPdo());
    }

    /**
     * Get the default connection name.
	 * 得到默认连接
     *
     * @return string
     */
    public function getDefaultConnection()
    {
        return $this->app['config']['database.default'];
    }

    /**
     * Set the default connection name.
	 * 设置默认连接
     *
     * @param  string  $name
     * @return void
     */
    public function setDefaultConnection($name)
    {
        $this->app['config']['database.default'] = $name;
    }

    /**
     * Get all of the support drivers.
	 * 得到所有支持驱动
     *
     * @return array
     */
    public function supportedDrivers()
    {
        return ['mysql', 'pgsql', 'sqlite', 'sqlsrv'];
    }

    /**
     * Get all of the drivers that are actually available.
	 * 得到所有可用的驱动程序
     *
     * @return array
     */
    public function availableDrivers()
    {
        return array_intersect(
            $this->supportedDrivers(),
            str_replace('dblib', 'sqlsrv', PDO::getAvailableDrivers())
        );
    }

    /**
     * Register an extension connection resolver.
	 * 注册扩展连接解析器
     *
     * @param  string  $name
     * @param  callable  $resolver
     * @return void
     */
    public function extend($name, callable $resolver)
    {
        $this->extensions[$name] = $resolver;
    }

    /**
     * Return all of the created connections.
	 * 返回所有已创建连接
     *
     * @return array
     */
    public function getConnections()
    {
        return $this->connections;
    }

    /**
     * Set the database reconnector callback.
	 * 设置数据库重接器回调
     *
     * @param  callable  $reconnector
     * @return void
     */
    public function setReconnector(callable $reconnector)
    {
        $this->reconnector = $reconnector;
    }

    /**
     * Dynamically pass methods to the default connection.
	 * 动态调取方法
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

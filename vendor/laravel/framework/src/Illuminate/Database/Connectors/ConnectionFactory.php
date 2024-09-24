<?php
/**
 * 数据库，连接工厂
 */

namespace Illuminate\Database\Connectors;

use Illuminate\Contracts\Container\Container;
use Illuminate\Database\Connection;
use Illuminate\Database\MySqlConnection;
use Illuminate\Database\PostgresConnection;
use Illuminate\Database\SQLiteConnection;
use Illuminate\Database\SqlServerConnection;
use Illuminate\Support\Arr;
use InvalidArgumentException;
use PDOException;

class ConnectionFactory
{
    /**
     * The IoC container instance.
	 * IoC容器实例
     *
     * @var \Illuminate\Contracts\Container\Container
     */
    protected $container;

    /**
     * Create a new connection factory instance.
	 * 创建新的连接工厂实例
     *
     * @param  \Illuminate\Contracts\Container\Container  $container
     * @return void
     */
    public function __construct(Container $container)
    {
        $this->container = $container;
    }

    /**
     * Establish a PDO connection based on the configuration.
	 * 根据配置信息建立PDO连接
     *
     * @param  array  $config
     * @param  string|null  $name
     * @return \Illuminate\Database\Connection
     */
    public function make(array $config, $name = null)
    {
        $config = $this->parseConfig($config, $name);

        if (isset($config['read'])) {
            return $this->createReadWriteConnection($config);
        }

        return $this->createSingleConnection($config);
    }

    /**
     * Parse and prepare the database configuration.
	 * 解析并准备数据库配置
     *
     * @param  array  $config
     * @param  string  $name
     * @return array
     */
    protected function parseConfig(array $config, $name)
    {
        return Arr::add(Arr::add($config, 'prefix', ''), 'name', $name);
    }

    /**
     * Create a single database connection instance.
	 * 创建单数据库连接实例
     *
     * @param  array  $config
     * @return \Illuminate\Database\Connection
     */
    protected function createSingleConnection(array $config)
    {
        $pdo = $this->createPdoResolver($config);

        return $this->createConnection(
            $config['driver'], $pdo, $config['database'], $config['prefix'], $config
        );
    }

    /**
     * Create a single database connection instance.
	 * 创建数据库读写实例(翻译不对改了)
     *
     * @param  array  $config
     * @return \Illuminate\Database\Connection
     */
    protected function createReadWriteConnection(array $config)
    {
        $connection = $this->createSingleConnection($this->getWriteConfig($config));

        return $connection->setReadPdo($this->createReadPdo($config));
    }

    /**
     * Create a new PDO instance for reading.
	 * 创建新的PDO实例用于读取 
     *
     * @param  array  $config
     * @return \Closure
     */
    protected function createReadPdo(array $config)
    {
        return $this->createPdoResolver($this->getReadConfig($config));
    }

    /**
     * Get the read configuration for a read / write connection.
	 * 得到读配置从读写连接
     *
     * @param  array  $config
     * @return array
     */
    protected function getReadConfig(array $config)
    {
        return $this->mergeReadWriteConfig(
            $config, $this->getReadWriteConfig($config, 'read')
        );
    }

    /**
     * Get the read configuration for a read / write connection.
	 * 得到读配置从读写连接
     *
     * @param  array  $config
     * @return array
     */
    protected function getWriteConfig(array $config)
    {
        return $this->mergeReadWriteConfig(
            $config, $this->getReadWriteConfig($config, 'write')
        );
    }

    /**
     * Get a read / write level configuration.
	 * 得到读写级别配置
     *
     * @param  array  $config
     * @param  string  $type
     * @return array
     */
    protected function getReadWriteConfig(array $config, $type)
    {
        return isset($config[$type][0])
                        ? Arr::random($config[$type])
                        : $config[$type];
    }

    /**
     * Merge a configuration for a read / write connection.
	 * 合并读取连接配置
     *
     * @param  array  $config
     * @param  array  $merge
     * @return array
     */
    protected function mergeReadWriteConfig(array $config, array $merge)
    {
        return Arr::except(array_merge($config, $merge), ['read', 'write']);
    }

    /**
     * Create a new Closure that resolves to a PDO instance.
	 * 创建一个解析为PDO实例的新闭包
     *
     * @param  array  $config
     * @return \Closure
     */
    protected function createPdoResolver(array $config)
    {
        return array_key_exists('host', $config)
                            ? $this->createPdoResolverWithHosts($config)
                            : $this->createPdoResolverWithoutHosts($config);
    }

    /**
     * Create a new Closure that resolves to a PDO instance with a specific host or an array of hosts.
	 * 创建新的闭包，解析为具有特定主机或主机数组的PDO实例
     *
     * @param  array  $config
     * @return \Closure
     */
    protected function createPdoResolverWithHosts(array $config)
    {
        return function () use ($config) {
            foreach (Arr::shuffle($hosts = $this->parseHosts($config)) as $key => $host) {
                $config['host'] = $host;

                try {
                    return $this->createConnector($config)->connect($config);
                } catch (PDOException $e) {
                    continue;
                }
            }

            throw $e;
        };
    }

    /**
     * Parse the hosts configuration item into an array.
	 * 将hosts配置项解析为数组
     *
     * @param  array  $config
     * @return array
     *
     * @throws \InvalidArgumentException
     */
    protected function parseHosts(array $config)
    {
        $hosts = Arr::wrap($config['host']);

        if (empty($hosts)) {
            throw new InvalidArgumentException('Database hosts array is empty.');
        }

        return $hosts;
    }

    /**
     * Create a new Closure that resolves to a PDO instance where there is no configured host.
	 * 创建一个新的Closure，解析到一个没有配置主机的PDO实例。
     *
     * @param  array  $config
     * @return \Closure
     */
    protected function createPdoResolverWithoutHosts(array $config)
    {
        return function () use ($config) {
            return $this->createConnector($config)->connect($config);
        };
    }

    /**
     * Create a connector instance based on the configuration.
	 * 根据配置创建连接器实例
     *
     * @param  array  $config
     * @return \Illuminate\Database\Connectors\ConnectorInterface
     *
     * @throws \InvalidArgumentException
     */
    public function createConnector(array $config)
    {
        if (! isset($config['driver'])) {
            throw new InvalidArgumentException('A driver must be specified.');
        }

        if ($this->container->bound($key = "db.connector.{$config['driver']}")) {
            return $this->container->make($key);
        }

		//核心代码，根据驱动选择连接器
        switch ($config['driver']) {
            case 'mysql':
                return new MySqlConnector;
            case 'pgsql':
                return new PostgresConnector;
            case 'sqlite':
                return new SQLiteConnector;
            case 'sqlsrv':
                return new SqlServerConnector;
        }

        throw new InvalidArgumentException("Unsupported driver [{$config['driver']}]");
    }

    /**
     * Create a new connection instance.
	 * 创建新的连接实例
     *
     * @param  string  $driver
     * @param  \PDO|\Closure  $connection
     * @param  string  $database
     * @param  string  $prefix
     * @param  array  $config
     * @return \Illuminate\Database\Connection
     *
     * @throws \InvalidArgumentException
     */
    protected function createConnection($driver, $connection, $database, $prefix = '', array $config = [])
    {
        if ($resolver = Connection::getResolver($driver)) {
            return $resolver($connection, $database, $prefix, $config);
        }

        switch ($driver) {
            case 'mysql':
                return new MySqlConnection($connection, $database, $prefix, $config);
            case 'pgsql':
                return new PostgresConnection($connection, $database, $prefix, $config);
            case 'sqlite':
                return new SQLiteConnection($connection, $database, $prefix, $config);
            case 'sqlsrv':
                return new SqlServerConnection($connection, $database, $prefix, $config);
        }

        throw new InvalidArgumentException("Unsupported driver [{$driver}]");
    }
}

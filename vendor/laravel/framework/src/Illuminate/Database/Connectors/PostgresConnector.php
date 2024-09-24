<?php
/**
 * 数据库，Postgres连接器
 */

namespace Illuminate\Database\Connectors;

use PDO;

class PostgresConnector extends Connector implements ConnectorInterface
{
    /**
     * The default PDO connection options.
	 * 默认PDO连接选项
     *
     * @var array
     */
    protected $options = [
        PDO::ATTR_CASE => PDO::CASE_NATURAL,
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_ORACLE_NULLS => PDO::NULL_NATURAL,
        PDO::ATTR_STRINGIFY_FETCHES => false,
    ];

    /**
     * Establish a database connection.
	 * 建立数据库连接
     *
     * @param  array  $config
     * @return \PDO
     */
    public function connect(array $config)
    {
        // First we'll create the basic DSN and connection instance connecting to the
        // using the configuration option specified by the developer. We will also
        // set the default character set on the connections to UTF-8 by default.
		// 首先，我们将使用开发人员指定的配置选项创建连接到的基本DSN和连接实例。
		// 我们也将将连接上的设置默认字符集设置为UTF-8。
        $connection = $this->createConnection(
            $this->getDsn($config), $config, $this->getOptions($config)
        );

        $this->configureEncoding($connection, $config);

        // Next, we will check to see if a timezone has been specified in this config
        // and if it has we will issue a statement to modify the timezone with the
        // database. Setting this DB timezone is an optional configuration item.
		// 接下来，我们将检查此配置中是否指定了时区，
		// 如果指定了我们将发出一条语句，使用数据库修改时区。
		// 设置此数据库时区是一个可选配置项。
        $this->configureTimezone($connection, $config);

        $this->configureSchema($connection, $config);

        // Postgres allows an application_name to be set by the user and this name is
        // used to when monitoring the application with pg_stat_activity. So we'll
        // determine if the option has been specified and run a statement if so.
		// Postgres允许用户设置application_name，
		// 在使用pg_stat_activity监视应用程序时使用此名称。
		// 因此，我们将确定是否已指定该选项，如果已指定，则运行一条语句。
        $this->configureApplicationName($connection, $config);

        return $connection;
    }

    /**
     * Set the connection character set and collation.
	 * 设置连接字符集和排序规则
     *
     * @param  \PDO  $connection
     * @param  array  $config
     * @return void
     */
    protected function configureEncoding($connection, $config)
    {
        if (! isset($config['charset'])) {
            return;
        }

        $connection->prepare("set names '{$config['charset']}'")->execute();
    }

    /**
     * Set the timezone on the connection.
	 * 设置连接的时区
     *
     * @param  \PDO  $connection
     * @param  array  $config
     * @return void
     */
    protected function configureTimezone($connection, array $config)
    {
        if (isset($config['timezone'])) {
            $timezone = $config['timezone'];

            $connection->prepare("set time zone '{$timezone}'")->execute();
        }
    }

    /**
     * Set the schema on the connection.
	 * 设置连接模式
     *
     * @param  \PDO  $connection
     * @param  array  $config
     * @return void
     */
    protected function configureSchema($connection, $config)
    {
        if (isset($config['schema'])) {
            $schema = $this->formatSchema($config['schema']);

            $connection->prepare("set search_path to {$schema}")->execute();
        }
    }

    /**
     * Format the schema for the DSN.
	 * 格式化DSN模式
     *
     * @param  array|string  $schema
     * @return string
     */
    protected function formatSchema($schema)
    {
        if (is_array($schema)) {
            return '"'.implode('", "', $schema).'"';
        }

        return '"'.$schema.'"';
    }

    /**
     * Set the schema on the connection.
	 * 设置模式在连接上
     *
     * @param  \PDO  $connection
     * @param  array  $config
     * @return void
     */
    protected function configureApplicationName($connection, $config)
    {
        if (isset($config['application_name'])) {
            $applicationName = $config['application_name'];

            $connection->prepare("set application_name to '$applicationName'")->execute();
        }
    }

    /**
     * Create a DSN string from a configuration.
	 * 创建DNS字符串从配置
     *
     * @param  array  $config
     * @return string
     */
    protected function getDsn(array $config)
    {
        // First we will create the basic DSN setup as well as the port if it is in
        // in the configuration options. This will give us the basic DSN we will
        // need to establish the PDO connections and return them back for use.
		// 首先，我们将创建基本的DSN设置以及端口（如果端口在配置选项中）。
		// 这将为我们提供建立PDO连接并将其返回使用所需的基本DSN。
        extract($config, EXTR_SKIP);

        $host = isset($host) ? "host={$host};" : '';

        $dsn = "pgsql:{$host}dbname={$database}";

        // If a port was specified, we will add it to this Postgres DSN connections
        // format. Once we have done that we are ready to return this connection
        // string back out for usage, as this has been fully constructed here.
		// 如果端口存在，我们将创建基本的DSN连接格式。
		// 一旦这样做了，我们已准备好将此连接返回使用，因为它已在此处完全构建。
        if (isset($config['port'])) {
            $dsn .= ";port={$port}";
        }

        return $this->addSslOptions($dsn, $config);
    }

    /**
     * Add the SSL options to the DSN.
	 * 将SSL选项添加到DSN
     *
     * @param  string  $dsn
     * @param  array  $config
     * @return string
     */
    protected function addSslOptions($dsn, array $config)
    {
        foreach (['sslmode', 'sslcert', 'sslkey', 'sslrootcert'] as $option) {
            if (isset($config[$option])) {
                $dsn .= ";{$option}={$config[$option]}";
            }
        }

        return $dsn;
    }
}

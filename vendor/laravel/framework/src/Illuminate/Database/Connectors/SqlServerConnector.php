<?php
/**
 * 数据库，SqlServer连接器
 */

namespace Illuminate\Database\Connectors;

use Illuminate\Support\Arr;
use PDO;

class SqlServerConnector extends Connector implements ConnectorInterface
{
    /**
     * The PDO connection options.
	 * PDO连接选项
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
        $options = $this->getOptions($config);

        return $this->createConnection($this->getDsn($config), $config, $options);
    }

    /**
     * Create a DSN string from a configuration.
	 * 创建DSN字符串从配置
     *
     * @param  array  $config
     * @return string
     */
    protected function getDsn(array $config)
    {
        // First we will create the basic DSN setup as well as the port if it is in
        // in the configuration options. This will give us the basic DSN we will
        // need to establish the PDO connections and return them back for use.
		// 首先我们将创建基本的DSN设置以及端口，如果存在在配置选项中。
		// 这将为我们提供基本的DSN，需要建立PDO连接并将其返回使用。
        if ($this->prefersOdbc($config)) {
            return $this->getOdbcDsn($config);
        }

        if (in_array('sqlsrv', $this->getAvailableDrivers())) {
            return $this->getSqlSrvDsn($config);
        } else {
            return $this->getDblibDsn($config);
        }
    }

    /**
     * Determine if the database configuration prefers ODBC.
	 * 确定数据库配置是否倾向于ODBC
     *
     * @param  array  $config
     * @return bool
     */
    protected function prefersOdbc(array $config)
    {
        return in_array('odbc', $this->getAvailableDrivers()) &&
               ($config['odbc'] ?? null) === true;
    }

    /**
     * Get the DSN string for a DbLib connection.
	 * 得到DbLib连接的DSN字符串
     *
     * @param  array  $config
     * @return string
     */
    protected function getDblibDsn(array $config)
    {
        return $this->buildConnectString('dblib', array_merge([
            'host' => $this->buildHostString($config, ':'),
            'dbname' => $config['database'],
        ], Arr::only($config, ['appname', 'charset', 'version'])));
    }

    /**
     * Get the DSN string for an ODBC connection.
	 * 得到ODBC连接的DSN字符串
     *
     * @param  array  $config
     * @return string
     */
    protected function getOdbcDsn(array $config)
    {
        return isset($config['odbc_datasource_name'])
                    ? 'odbc:'.$config['odbc_datasource_name'] : '';
    }

    /**
     * Get the DSN string for a SqlSrv connection.
	 * 得到SqlSrv连接的DSN字符串
     *
     * @param  array  $config
     * @return string
     */
    protected function getSqlSrvDsn(array $config)
    {
        $arguments = [
            'Server' => $this->buildHostString($config, ','),
        ];

        if (isset($config['database'])) {
            $arguments['Database'] = $config['database'];
        }

        if (isset($config['readonly'])) {
            $arguments['ApplicationIntent'] = 'ReadOnly';
        }

        if (isset($config['pooling']) && $config['pooling'] === false) {
            $arguments['ConnectionPooling'] = '0';
        }

        if (isset($config['appname'])) {
            $arguments['APP'] = $config['appname'];
        }

        if (isset($config['encrypt'])) {
            $arguments['Encrypt'] = $config['encrypt'];
        }

        if (isset($config['trust_server_certificate'])) {
            $arguments['TrustServerCertificate'] = $config['trust_server_certificate'];
        }

        if (isset($config['multiple_active_result_sets']) && $config['multiple_active_result_sets'] === false) {
            $arguments['MultipleActiveResultSets'] = 'false';
        }

        if (isset($config['transaction_isolation'])) {
            $arguments['TransactionIsolation'] = $config['transaction_isolation'];
        }

        if (isset($config['multi_subnet_failover'])) {
            $arguments['MultiSubnetFailover'] = $config['multi_subnet_failover'];
        }

        if (isset($config['column_encryption'])) {
            $arguments['ColumnEncryption'] = $config['column_encryption'];
        }

        if (isset($config['key_store_authentication'])) {
            $arguments['KeyStoreAuthentication'] = $config['key_store_authentication'];
        }

        if (isset($config['key_store_principal_id'])) {
            $arguments['KeyStorePrincipalId'] = $config['key_store_principal_id'];
        }

        if (isset($config['key_store_secret'])) {
            $arguments['KeyStoreSecret'] = $config['key_store_secret'];
        }

        return $this->buildConnectString('sqlsrv', $arguments);
    }

    /**
     * Build a connection string from the given arguments.
	 * 构建连接字符串根据给定的参数
     *
     * @param  string  $driver
     * @param  array  $arguments
     * @return string
     */
    protected function buildConnectString($driver, array $arguments)
    {
        return $driver.':'.implode(';', array_map(function ($key) use ($arguments) {
            return sprintf('%s=%s', $key, $arguments[$key]);
        }, array_keys($arguments)));
    }

    /**
     * Build a host string from the given configuration.
	 * 构建主机字符串根据给定的配置
     *
     * @param  array  $config
     * @param  string  $separator
     * @return string
     */
    protected function buildHostString(array $config, $separator)
    {
        if (empty($config['port'])) {
            return $config['host'];
        }

        return $config['host'].$separator.$config['port'];
    }

    /**
     * Get the available PDO drivers.
	 * 得到可用的PDO驱动程序
     *
     * @return array
     */
    protected function getAvailableDrivers()
    {
        return PDO::getAvailableDrivers();
    }
}

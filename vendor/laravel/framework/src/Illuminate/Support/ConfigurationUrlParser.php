<?php
/**
 * 支持，配置Url解析器
 */

namespace Illuminate\Support;

use InvalidArgumentException;

class ConfigurationUrlParser
{
    /**
     * The drivers aliases map.
	 * 驱动别名映射
     *
     * @var array
     */
    protected static $driverAliases = [
        'mssql' => 'sqlsrv',
        'mysql2' => 'mysql', // RDS
        'postgres' => 'pgsql',
        'postgresql' => 'pgsql',
        'sqlite3' => 'sqlite',
        'redis' => 'tcp',
        'rediss' => 'tls',
    ];

    /**
     * Parse the database configuration, hydrating options using a database configuration URL if possible.
	 * 解析数据库配置，如果可能的话，使用数据库配置URL添加选项。
     *
     * @param  array|string  $config
     * @return array
     */
    public function parseConfiguration($config)
    {
        if (is_string($config)) {
            $config = ['url' => $config];
        }

        $url = $config['url'] ?? null;

        $config = Arr::except($config, 'url');

        if (! $url) {
            return $config;
        }

        $rawComponents = $this->parseUrl($url);

        $decodedComponents = $this->parseStringsToNativeTypes(
            array_map('rawurldecode', $rawComponents)
        );

        return array_merge(
            $config,
            $this->getPrimaryOptions($decodedComponents),
            $this->getQueryOptions($rawComponents)
        );
    }

    /**
     * Get the primary database connection options.
	 * 得到主数据库连接选项
     *
     * @param  array  $url
     * @return array
     */
    protected function getPrimaryOptions($url)
    {
        return array_filter([
            'driver' => $this->getDriver($url),
            'database' => $this->getDatabase($url),
            'host' => $url['host'] ?? null,
            'port' => $url['port'] ?? null,
            'username' => $url['user'] ?? null,
            'password' => $url['pass'] ?? null,
        ], function ($value) {
            return ! is_null($value);
        });
    }

    /**
     * Get the database driver from the URL.
	 * 得到数据库驱动从URL
     *
     * @param  array  $url
     * @return string|null
     */
    protected function getDriver($url)
    {
        $alias = $url['scheme'] ?? null;

        if (! $alias) {
            return;
        }

        return static::$driverAliases[$alias] ?? $alias;
    }

    /**
     * Get the database name from the URL.
	 * 得到数据库名称从URL
     *
     * @param  array  $url
     * @return string|null
     */
    protected function getDatabase($url)
    {
        $path = $url['path'] ?? null;

        return $path && $path !== '/' ? substr($path, 1) : null;
    }

    /**
     * Get all of the additional database options from the query string.
	 * 得到所有其他数据库选项从查询字符串中
     *
     * @param  array  $url
     * @return array
     */
    protected function getQueryOptions($url)
    {
        $queryString = $url['query'] ?? null;

        if (! $queryString) {
            return [];
        }

        $query = [];

        parse_str($queryString, $query);

        return $this->parseStringsToNativeTypes($query);
    }

    /**
     * Parse the string URL to an array of components.
	 * 解析字符串URL为组件数组
     *
     * @param  string  $url
     * @return array
     *
     * @throws \InvalidArgumentException
     */
    protected function parseUrl($url)
    {
        $url = preg_replace('#^(sqlite3?):///#', '$1://null/', $url);

        $parsedUrl = parse_url($url);

        if ($parsedUrl === false) {
            throw new InvalidArgumentException('The database configuration URL is malformed.');
        }

        return $parsedUrl;
    }

    /**
     * Convert string casted values to their native types.
	 * 转换字符串强制转换值为其本机类型
     *
     * @param  mixed  $value
     * @return mixed
     */
    protected function parseStringsToNativeTypes($value)
    {
        if (is_array($value)) {
            return array_map([$this, 'parseStringsToNativeTypes'], $value);
        }

        if (! is_string($value)) {
            return $value;
        }

        $parsedValue = json_decode($value, true);

        if (json_last_error() === JSON_ERROR_NONE) {
            return $parsedValue;
        }

        return $value;
    }

    /**
     * Get all of the current drivers aliases.
	 * 得到当前所有驱动的别名
     *
     * @return array
     */
    public static function getDriverAliases()
    {
        return static::$driverAliases;
    }

    /**
     * Add the given driver alias to the driver aliases array.
	 * 添加给定的驱动别名到驱动别名数组中
     *
     * @param  string  $alias
     * @param  string  $driver
     * @return void
     */
    public static function addDriverAlias($alias, $driver)
    {
        static::$driverAliases[$alias] = $driver;
    }
}

<?php
/**
 * 数据库连接解析
 */

namespace Illuminate\Database;

class ConnectionResolver implements ConnectionResolverInterface
{
    /**
     * All of the registered connections.
	 * 所有注册连接
     *
     * @var array
     */
    protected $connections = [];

    /**
     * The default connection name.
	 * 默认连接名
     *
     * @var string
     */
    protected $default;

    /**
     * Create a new connection resolver instance.
	 * 创建新的连接解析实例
     *
     * @param  array  $connections
     * @return void
     */
    public function __construct(array $connections = [])
    {
        foreach ($connections as $name => $connection) {
            $this->addConnection($name, $connection);
        }
    }

    /**
     * Get a database connection instance.
	 * 得到数据库连接实例
     *
     * @param  string|null  $name
     * @return \Illuminate\Database\ConnectionInterface
     */
    public function connection($name = null)
    {
        if (is_null($name)) {
            $name = $this->getDefaultConnection();
        }

        return $this->connections[$name];
    }

    /**
     * Add a connection to the resolver.
	 * 添加连接解析器
     *
     * @param  string  $name
     * @param  \Illuminate\Database\ConnectionInterface  $connection
     * @return void
     */
    public function addConnection($name, ConnectionInterface $connection)
    {
        $this->connections[$name] = $connection;
    }

    /**
     * Check if a connection has been registered.
	 * 检查连接是否已被注册
     *
     * @param  string  $name
     * @return bool
     */
    public function hasConnection($name)
    {
        return isset($this->connections[$name]);
    }

    /**
     * Get the default connection name.
	 * 得到默认连接名
     *
     * @return string
     */
    public function getDefaultConnection()
    {
        return $this->default;
    }

    /**
     * Set the default connection name.
	 * 设置默认连接名
     *
     * @param  string  $name
     * @return void
     */
    public function setDefaultConnection($name)
    {
        $this->default = $name;
    }
}

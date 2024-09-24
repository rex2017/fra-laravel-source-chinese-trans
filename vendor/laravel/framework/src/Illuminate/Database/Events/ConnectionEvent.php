<?php
/**
 * 数据库，连接事件抽象类
 */

namespace Illuminate\Database\Events;

abstract class ConnectionEvent
{
    /**
     * The name of the connection.
	 * 连接名
     *
     * @var string
     */
    public $connectionName;

    /**
     * The database connection instance.
	 * 数据库连接实例
     *
     * @var \Illuminate\Database\Connection
     */
    public $connection;

    /**
     * Create a new event instance.
	 * 创建新的事件实例
     *
     * @param  \Illuminate\Database\Connection  $connection
     * @return void
     */
    public function __construct($connection)
    {
        $this->connection = $connection;
        $this->connectionName = $connection->getName();
    }
}

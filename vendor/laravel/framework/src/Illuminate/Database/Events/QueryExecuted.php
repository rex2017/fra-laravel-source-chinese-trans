<?php
/**
 * 数据库，查询执行
 */

namespace Illuminate\Database\Events;

class QueryExecuted
{
    /**
     * The SQL query that was executed.
	 * 执行的SQL查询
     *
     * @var string
     */
    public $sql;

    /**
     * The array of query bindings.
	 * 查询绑定数组
     *
     * @var array
     */
    public $bindings;

    /**
     * The number of milliseconds it took to execute the query.
	 * 执行查询所需的毫秒数
     *
     * @var float
     */
    public $time;

    /**
     * The database connection instance.
	 * 数据库连接实例
     *
     * @var \Illuminate\Database\Connection
     */
    public $connection;

    /**
     * The database connection name.
	 * 数据库连接名
     *
     * @var string
     */
    public $connectionName;

    /**
     * Create a new event instance.
	 * 创建新的事件实例
     *
     * @param  string  $sql
     * @param  array  $bindings
     * @param  float|null  $time
     * @param  \Illuminate\Database\Connection  $connection
     * @return void
     */
    public function __construct($sql, $bindings, $time, $connection)
    {
        $this->sql = $sql;
        $this->time = $time;
        $this->bindings = $bindings;
        $this->connection = $connection;
        $this->connectionName = $connection->getName();
    }
}

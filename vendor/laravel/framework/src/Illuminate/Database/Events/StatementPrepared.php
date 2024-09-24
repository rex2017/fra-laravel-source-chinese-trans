<?php
/**
 * 数据库，准备语句
 */

namespace Illuminate\Database\Events;

class StatementPrepared
{
    /**
     * The database connection instance.
	 * 数据库连接实例
     *
     * @var \Illuminate\Database\Connection
     */
    public $connection;

    /**
     * The PDO statement.
	 * PDO语句
	 * 
     *
     * @var \PDOStatement
     */
    public $statement;

    /**
     * Create a new event instance.
	 * 创建新的事件实例
     *
     * @param  \Illuminate\Database\Connection  $connection
     * @param  \PDOStatement  $statement
     * @return void
     */
    public function __construct($connection, $statement)
    {
        $this->statement = $statement;
        $this->connection = $connection;
    }
}

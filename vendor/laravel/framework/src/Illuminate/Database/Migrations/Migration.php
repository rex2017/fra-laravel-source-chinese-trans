<?php
/**
 * 数据库，迁移抽象类
 */

namespace Illuminate\Database\Migrations;

abstract class Migration
{
    /**
     * The name of the database connection to use.
	 * 数据库连接名
     *
     * @var string|null
     */
    protected $connection;

    /**
     * Enables, if supported, wrapping the migration within a transaction.
	 * 启用(如果支持)在事务中包装迁移
     *
     * @var bool
     */
    public $withinTransaction = true;

    /**
     * Get the migration connection name.
	 * 得到迁移连接名
     *
     * @return string|null
     */
    public function getConnection()
    {
        return $this->connection;
    }
}

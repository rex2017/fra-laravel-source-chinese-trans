<?php
/**
 * 数据库连接接口
 */

namespace Illuminate\Database;

use Closure;

interface ConnectionInterface
{
    /**
     * Begin a fluent query against a database table.
	 * 开始数据表的一个流畅查询
     *
     * @param  \Closure|\Illuminate\Database\Query\Builder|string  $table
     * @param  string|null  $as
     * @return \Illuminate\Database\Query\Builder
     */
    public function table($table, $as = null);

    /**
     * Get a new raw query expression.
	 * 得到一个原始查询表达式
     *
     * @param  mixed  $value
     * @return \Illuminate\Database\Query\Expression
     */
    public function raw($value);

    /**
     * Run a select statement and return a single result.
	 * 执行一个查询语言返回单个结果
     *
     * @param  string  $query
     * @param  array  $bindings
     * @param  bool  $useReadPdo
     * @return mixed
     */
    public function selectOne($query, $bindings = [], $useReadPdo = true);

    /**
     * Run a select statement against the database.
	 * 运行查询语句
     *
     * @param  string  $query
     * @param  array  $bindings
     * @param  bool  $useReadPdo
     * @return array
     */
    public function select($query, $bindings = [], $useReadPdo = true);

    /**
     * Run a select statement against the database and returns a generator.
	 * 运行select语句并返回生成器对数据库
     *
     * @param  string  $query
     * @param  array  $bindings
     * @param  bool  $useReadPdo
     * @return \Generator
     */
    public function cursor($query, $bindings = [], $useReadPdo = true);

    /**
     * Run an insert statement against the database.
	 * 运行插入语句
     *
     * @param  string  $query
     * @param  array  $bindings
     * @return bool
     */
    public function insert($query, $bindings = []);

    /**
     * Run an update statement against the database.
	 * 运行更新语句
     *
     * @param  string  $query
     * @param  array  $bindings
     * @return int
     */
    public function update($query, $bindings = []);

    /**
     * Run a delete statement against the database.
	 * 运行删除语句
     *
     * @param  string  $query
     * @param  array  $bindings
     * @return int
     */
    public function delete($query, $bindings = []);

    /**
     * Execute an SQL statement and return the boolean result.
	 * 执行SQL语句
     *
     * @param  string  $query
     * @param  array  $bindings
     * @return bool
     */
    public function statement($query, $bindings = []);

    /**
     * Run an SQL statement and get the number of rows affected.
	 * 运行SQL语句，返回影响行数
     *
     * @param  string  $query
     * @param  array  $bindings
     * @return int
     */
    public function affectingStatement($query, $bindings = []);

    /**
     * Run a raw, unprepared query against the PDO connection.
	 * 运行一个未准备的原始查询对PDO连接
     *
     * @param  string  $query
     * @return bool
     */
    public function unprepared($query);

    /**
     * Prepare the query bindings for execution.
	 * 准备执行查询绑定
     *
     * @param  array  $bindings
     * @return array
     */
    public function prepareBindings(array $bindings);

    /**
     * Execute a Closure within a transaction.
	 * 执行闭包在事务中
     *
     * @param  \Closure  $callback
     * @param  int  $attempts
     * @return mixed
     *
     * @throws \Throwable
     */
    public function transaction(Closure $callback, $attempts = 1);

    /**
     * Start a new database transaction.
	 * 开始一个事务
     *
     * @return void
     */
    public function beginTransaction();

    /**
     * Commit the active database transaction.
	 * 提交事务
     *
     * @return void
     */
    public function commit();

    /**
     * Rollback the active database transaction.
	 * 回滚活动数据库事务
     *
     * @return void
     */
    public function rollBack();

    /**
     * Get the number of active transactions.
	 * 得到事务级别
     *
     * @return int
     */
    public function transactionLevel();

    /**
     * Execute the given callback in "dry run" mode.
	 * 以"预演"模式执行给定的回调函数
     *
     * @param  \Closure  $callback
     * @return array
     */
    public function pretend(Closure $callback);
}

<?php
/**
 * 数据库查询异常
 */

namespace Illuminate\Database;

use Illuminate\Support\Str;
use PDOException;

class QueryException extends PDOException
{
    /**
     * The SQL for the query.
	 * 查询SQL
     *
     * @var string
     */
    protected $sql;

    /**
     * The bindings for the query.
	 * 绑定查询
     *
     * @var array
     */
    protected $bindings;

    /**
     * Create a new query exception instance.
	 * 创建新查询异常实例
     *
     * @param  string  $sql
     * @param  array  $bindings
     * @param  \Exception  $previous
     * @return void
     */
    public function __construct($sql, array $bindings, $previous)
    {
        parent::__construct('', 0, $previous);

        $this->sql = $sql;
        $this->bindings = $bindings;
        $this->code = $previous->getCode();
        $this->message = $this->formatMessage($sql, $bindings, $previous);

        if ($previous instanceof PDOException) {
            $this->errorInfo = $previous->errorInfo;
        }
    }

    /**
     * Format the SQL error message.
	 * 格式化SQL错误信息
     *
     * @param  string  $sql
     * @param  array  $bindings
     * @param  \Exception  $previous
     * @return string
     */
    protected function formatMessage($sql, $bindings, $previous)
    {
        return $previous->getMessage().' (SQL: '.Str::replaceArray('?', $bindings, $sql).')';
    }

    /**
     * Get the SQL for the query.
	 * 得到查询SQL
     *
     * @return string
     */
    public function getSql()
    {
        return $this->sql;
    }

    /**
     * Get the bindings for the query.
	 * 得到绑定查询
     *
     * @return array
     */
    public function getBindings()
    {
        return $this->bindings;
    }
}

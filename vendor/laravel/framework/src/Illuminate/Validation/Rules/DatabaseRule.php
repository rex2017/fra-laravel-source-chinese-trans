<?php
/**
 * 验证，数据库规则
 */

namespace Illuminate\Validation\Rules;

use Closure;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

trait DatabaseRule
{
    /**
     * The table to run the query against.
	 * 要运行查询的表
     *
     * @var string
     */
    protected $table;

    /**
     * The column to check on.
	 * 要检查的列
     *
     * @var string
     */
    protected $column;

    /**
     * The extra where clauses for the query.
	 * 查询的额外where子句
     *
     * @var array
     */
    protected $wheres = [];

    /**
     * The array of custom query callbacks.
	 * 自定义查询回调函数数组
     *
     * @var array
     */
    protected $using = [];

    /**
     * Create a new rule instance.
	 * 创建新的规则实例
     *
     * @param  string  $table
     * @param  string  $column
     * @return void
     */
    public function __construct($table, $column = 'NULL')
    {
        $this->column = $column;

        $this->table = $this->resolveTableName($table);
    }

    /**
     * Resolves the name of the table from the given string.
	 * 解析表的名称从给定字符串中
     *
     * @param  string  $table
     * @return string
     */
    public function resolveTableName($table)
    {
        if (! Str::contains($table, '\\') || ! class_exists($table)) {
            return $table;
        }

        if (is_subclass_of($table, Model::class)) {
            return (new $table)->getTable();
        }

        return $table;
    }

    /**
     * Set a "where" constraint on the query.
	 * 设置"where"约束在查询上
     *
     * @param  \Closure|string  $column
     * @param  array|string|null  $value
     * @return $this
     */
    public function where($column, $value = null)
    {
        if (is_array($value)) {
            return $this->whereIn($column, $value);
        }

        if ($column instanceof Closure) {
            return $this->using($column);
        }

        $this->wheres[] = compact('column', 'value');

        return $this;
    }

    /**
     * Set a "where not" constraint on the query.
	 * 设置"where not"约束在查询上
     *
     * @param  string  $column
     * @param  array|string  $value
     * @return $this
     */
    public function whereNot($column, $value)
    {
        if (is_array($value)) {
            return $this->whereNotIn($column, $value);
        }

        return $this->where($column, '!'.$value);
    }

    /**
     * Set a "where null" constraint on the query.
	 * 设置"where null"约束在查询上
     *
     * @param  string  $column
     * @return $this
     */
    public function whereNull($column)
    {
        return $this->where($column, 'NULL');
    }

    /**
     * Set a "where not null" constraint on the query.
	 * 设置"where not null"约束在查询上
     *
     * @param  string  $column
     * @return $this
     */
    public function whereNotNull($column)
    {
        return $this->where($column, 'NOT_NULL');
    }

    /**
     * Set a "where in" constraint on the query.
	 * 设置"where in"约束在查询上
     *
     * @param  string  $column
     * @param  array  $values
     * @return $this
     */
    public function whereIn($column, array $values)
    {
        return $this->where(function ($query) use ($column, $values) {
            $query->whereIn($column, $values);
        });
    }

    /**
     * Set a "where not in" constraint on the query.
	 * 设置"where not in"约束在查询上
     *
     * @param  string  $column
     * @param  array  $values
     * @return $this
     */
    public function whereNotIn($column, array $values)
    {
        return $this->where(function ($query) use ($column, $values) {
            $query->whereNotIn($column, $values);
        });
    }

    /**
     * Register a custom query callback.
	 * 注册自定义查询回调
     *
     * @param  \Closure  $callback
     * @return $this
     */
    public function using(Closure $callback)
    {
        $this->using[] = $callback;

        return $this;
    }

    /**
     * Get the custom query callbacks for the rule.
	 * 得到规则的自定义查询回调
     *
     * @return array
     */
    public function queryCallbacks()
    {
        return $this->using;
    }

    /**
     * Format the where clauses.
	 * 格式化where子句
     *
     * @return string
     */
    protected function formatWheres()
    {
        return collect($this->wheres)->map(function ($where) {
            return $where['column'].','.'"'.str_replace('"', '""', $where['value']).'"';
        })->implode(',');
    }
}

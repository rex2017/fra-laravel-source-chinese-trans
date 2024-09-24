<?php
/**
 * 数据库语法抽象类
 */

namespace Illuminate\Database;

use Illuminate\Database\Query\Expression;
use Illuminate\Support\Traits\Macroable;

abstract class Grammar
{
    use Macroable;

    /**
     * The grammar table prefix.
	 * 语法表前缀
     *
     * @var string
     */
    protected $tablePrefix = '';

    /**
     * Wrap an array of values.
	 * 包装一个数组值
     *
     * @param  array  $values
     * @return array
     */
    public function wrapArray(array $values)
    {
        return array_map([$this, 'wrap'], $values);
    }

    /**
     * Wrap a table in keyword identifiers.
	 * 包装表用关键字标识符
     *
     * @param  \Illuminate\Database\Query\Expression|string  $table
     * @return string
     */
    public function wrapTable($table)
    {
        if (! $this->isExpression($table)) {
            return $this->wrap($this->tablePrefix.$table, true);
        }

        return $this->getValue($table);
    }

    /**
     * Wrap a value in keyword identifiers.
	 * 包装值在关键字标识符中
     *
     * @param  \Illuminate\Database\Query\Expression|string  $value
     * @param  bool  $prefixAlias
     * @return string
     */
    public function wrap($value, $prefixAlias = false)
    {
        if ($this->isExpression($value)) {
            return $this->getValue($value);
        }

        // If the value being wrapped has a column alias we will need to separate out
        // the pieces so we can wrap each of the segments of the expression on its
        // own, and then join these both back together using the "as" connector.
		// 如果被包装的值具有列别名，我们需要将这些片段分开，这样我们就可以单独包装表达式的每个片段，
		// 然后使用"as"连接器将它们重新连接在一起。
        if (stripos($value, ' as ') !== false) {
            return $this->wrapAliasedValue($value, $prefixAlias);
        }

        return $this->wrapSegments(explode('.', $value));
    }

    /**
     * Wrap a value that has an alias.
	 * 包装具有别名的值
     *
     * @param  string  $value
     * @param  bool  $prefixAlias
     * @return string
     */
    protected function wrapAliasedValue($value, $prefixAlias = false)
    {
        $segments = preg_split('/\s+as\s+/i', $value);

        // If we are wrapping a table we need to prefix the alias with the table prefix
        // as well in order to generate proper syntax. If this is a column of course
        // no prefix is necessary. The condition will be true when from wrapTable.
		// 如果我们包装一个表，我们需要给别名加上表前缀为了生成正确的语法。
		// 如果这是一列的话不需要剪树。当从wrapTable返回时，条件为真。
        if ($prefixAlias) {
            $segments[1] = $this->tablePrefix.$segments[1];
        }

        return $this->wrap($segments[0]).' as '.$this->wrapValue($segments[1]);
    }

    /**
     * Wrap the given value segments.
	 * 包装给定的值
     *
     * @param  array  $segments
     * @return string
     */
    protected function wrapSegments($segments)
    {
        return collect($segments)->map(function ($segment, $key) use ($segments) {
            return $key == 0 && count($segments) > 1
                            ? $this->wrapTable($segment)
                            : $this->wrapValue($segment);
        })->implode('.');
    }

    /**
     * Wrap a single string in keyword identifiers.
	 * 包装单个字符串在关键字标识符
     *
     * @param  string  $value
     * @return string
     */
    protected function wrapValue($value)
    {
        if ($value !== '*') {
            return '"'.str_replace('"', '""', $value).'"';
        }

        return $value;
    }

    /**
     * Convert an array of column names into a delimited string.
	 * 将列名数组转换为带分隔符的字符串
     *
     * @param  array  $columns
     * @return string
     */
    public function columnize(array $columns)
    {
        return implode(', ', array_map([$this, 'wrap'], $columns));
    }

    /**
     * Create query parameter place-holders for an array.
	 * 创建查询参数占位为数组
     *
     * @param  array  $values
     * @return string
     */
    public function parameterize(array $values)
    {
        return implode(', ', array_map([$this, 'parameter'], $values));
    }

    /**
     * Get the appropriate query parameter place-holder for a value.
	 * 得到值的适当查询参数占位符
     *
     * @param  mixed  $value
     * @return string
     */
    public function parameter($value)
    {
        return $this->isExpression($value) ? $this->getValue($value) : '?';
    }

    /**
     * Quote the given string literal.
	 * 引用给定的字符串字面值
     *
     * @param  string|array  $value
     * @return string
     */
    public function quoteString($value)
    {
        if (is_array($value)) {
            return implode(', ', array_map([$this, __FUNCTION__], $value));
        }

        return "'$value'";
    }

    /**
     * Determine if the given value is a raw expression.
	 * 确定给定的值是否是一个原始表达式
     *
     * @param  mixed  $value
     * @return bool
     */
    public function isExpression($value)
    {
        return $value instanceof Expression;
    }

    /**
     * Get the value of a raw expression.
	 * 得到原始表达式的值
     *
     * @param  \Illuminate\Database\Query\Expression  $expression
     * @return string
     */
    public function getValue($expression)
    {
        return $expression->getValue();
    }

    /**
     * Get the format for database stored dates.
	 * 得到数据库存储日期的格式
     *
     * @return string
     */
    public function getDateFormat()
    {
        return 'Y-m-d H:i:s';
    }

    /**
     * Get the grammar's table prefix.
	 * 得到表前缀
     *
     * @return string
     */
    public function getTablePrefix()
    {
        return $this->tablePrefix;
    }

    /**
     * Set the grammar's table prefix.
	 * 设置表前缀
     *
     * @param  string  $prefix
     * @return $this
     */
    public function setTablePrefix($prefix)
    {
        $this->tablePrefix = $prefix;

        return $this;
    }
}

<?php
/**
 * 数据库，SQLite语法
 */

namespace Illuminate\Database\Query\Grammars;

use Illuminate\Database\Query\Builder;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

class SQLiteGrammar extends Grammar
{
    /**
     * All of the available clause operators.
	 * 所有可用子句操作符
     *
     * @var array
     */
    protected $operators = [
        '=', '<', '>', '<=', '>=', '<>', '!=',
        'like', 'not like', 'ilike',
        '&', '|', '<<', '>>',
    ];

    /**
     * Compile the lock into SQL.
	 * 将锁编译成SQL
     *
     * @param  \Illuminate\Database\Query\Builder  $query
     * @param  bool|string  $value
     * @return string
     */
    protected function compileLock(Builder $query, $value)
    {
        return '';
    }

    /**
     * Wrap a union subquery in parentheses.
	 * 包装联合子查询在括号中
     *
     * @param  string  $sql
     * @return string
     */
    protected function wrapUnion($sql)
    {
        return 'select * from ('.$sql.')';
    }

    /**
     * Compile a "where date" clause.
	 * 编译"where date"子句
     *
     * @param  \Illuminate\Database\Query\Builder  $query
     * @param  array  $where
     * @return string
     */
    protected function whereDate(Builder $query, $where)
    {
        return $this->dateBasedWhere('%Y-%m-%d', $query, $where);
    }

    /**
     * Compile a "where day" clause.
	 * 编译"where day"子句
     *
     * @param  \Illuminate\Database\Query\Builder  $query
     * @param  array  $where
     * @return string
     */
    protected function whereDay(Builder $query, $where)
    {
        return $this->dateBasedWhere('%d', $query, $where);
    }

    /**
     * Compile a "where month" clause.
	 * 编译"where month"子句
     *
     * @param  \Illuminate\Database\Query\Builder  $query
     * @param  array  $where
     * @return string
     */
    protected function whereMonth(Builder $query, $where)
    {
        return $this->dateBasedWhere('%m', $query, $where);
    }

    /**
     * Compile a "where year" clause.
	 * 编译"where year"子句
     *
     * @param  \Illuminate\Database\Query\Builder  $query
     * @param  array  $where
     * @return string
     */
    protected function whereYear(Builder $query, $where)
    {
        return $this->dateBasedWhere('%Y', $query, $where);
    }

    /**
     * Compile a "where time" clause.
	 * 编译"where time"子句
     *
     * @param  \Illuminate\Database\Query\Builder  $query
     * @param  array  $where
     * @return string
     */
    protected function whereTime(Builder $query, $where)
    {
        return $this->dateBasedWhere('%H:%M:%S', $query, $where);
    }

    /**
     * Compile a date based where clause.
	 * 编译基于日期的where子句
     *
     * @param  string  $type
     * @param  \Illuminate\Database\Query\Builder  $query
     * @param  array  $where
     * @return string
     */
    protected function dateBasedWhere($type, Builder $query, $where)
    {
        $value = $this->parameter($where['value']);

        return "strftime('{$type}', {$this->wrap($where['column'])}) {$where['operator']} cast({$value} as text)";
    }

    /**
     * Compile a "JSON length" statement into SQL.
	 * 编译"JSON length"子句至SQL
     *
     * @param  string  $column
     * @param  string  $operator
     * @param  string  $value
     * @return string
     */
    protected function compileJsonLength($column, $operator, $value)
    {
        [$field, $path] = $this->wrapJsonFieldAndPath($column);

        return 'json_array_length('.$field.$path.') '.$operator.' '.$value;
    }

    /**
     * Compile an update statement into SQL.
	 * 编译update语句成SQL
     *
     * @param  \Illuminate\Database\Query\Builder  $query
     * @param  array  $values
     * @return string
     */
    public function compileUpdate(Builder $query, array $values)
    {
        if (isset($query->joins) || isset($query->limit)) {
            return $this->compileUpdateWithJoinsOrLimit($query, $values);
        }

        return parent::compileUpdate($query, $values);
    }

    /**
     * Compile an insert ignore statement into SQL.
	 * 编译插入忽略语句成SQL
     *
     * @param  \Illuminate\Database\Query\Builder  $query
     * @param  array  $values
     * @return string
     */
    public function compileInsertOrIgnore(Builder $query, array $values)
    {
        return Str::replaceFirst('insert', 'insert or ignore', $this->compileInsert($query, $values));
    }

    /**
     * Compile the columns for an update statement.
	 * 编译更新语句的列
     *
     * @param  \Illuminate\Database\Query\Builder  $query
     * @param  array  $values
     * @return string
     */
    protected function compileUpdateColumns(Builder $query, array $values)
    {
        $jsonGroups = $this->groupJsonColumnsForUpdate($values);

        return collect($values)->reject(function ($value, $key) {
            return $this->isJsonSelector($key);
        })->merge($jsonGroups)->map(function ($value, $key) use ($jsonGroups) {
            $column = last(explode('.', $key));

            $value = isset($jsonGroups[$key]) ? $this->compileJsonPatch($column, $value) : $this->parameter($value);

            return $this->wrap($column).' = '.$value;
        })->implode(', ');
    }

    /**
     * Group the nested JSON columns.
	 * 分组嵌套的JSON列
     *
     * @param  array  $values
     * @return array
     */
    protected function groupJsonColumnsForUpdate(array $values)
    {
        $groups = [];

        foreach ($values as $key => $value) {
            if ($this->isJsonSelector($key)) {
                Arr::set($groups, str_replace('->', '.', Str::after($key, '.')), $value);
            }
        }

        return $groups;
    }

    /**
     * Compile a "JSON" patch statement into SQL.
	 * 编译"JSON"补丁语句成SQL
     *
     * @param  string  $column
     * @param  mixed  $value
     * @return string
     */
    protected function compileJsonPatch($column, $value)
    {
        return "json_patch(ifnull({$this->wrap($column)}, json('{}')), json({$this->parameter($value)}))";
    }

    /**
     * Compile an update statement with joins or limit into SQL.
	 * 编译带有连接或限制的更新语句成SQL
     *
     * @param  \Illuminate\Database\Query\Builder  $query
     * @param  array  $values
     * @return string
     */
    protected function compileUpdateWithJoinsOrLimit(Builder $query, array $values)
    {
        $table = $this->wrapTable($query->from);

        $columns = $this->compileUpdateColumns($query, $values);

        $alias = last(preg_split('/\s+as\s+/i', $query->from));

        $selectSql = $this->compileSelect($query->select($alias.'.rowid'));

        return "update {$table} set {$columns} where {$this->wrap('rowid')} in ({$selectSql})";
    }

    /**
     * Prepare the bindings for an update statement.
	 * 准备绑定为更新语句
     *
     * @param  array  $bindings
     * @param  array  $values
     * @return array
     */
    public function prepareBindingsForUpdate(array $bindings, array $values)
    {
        $groups = $this->groupJsonColumnsForUpdate($values);

        $values = collect($values)->reject(function ($value, $key) {
            return $this->isJsonSelector($key);
        })->merge($groups)->map(function ($value) {
            return is_array($value) ? json_encode($value) : $value;
        })->all();

        $cleanBindings = Arr::except($bindings, 'select');

        return array_values(
            array_merge($values, Arr::flatten($cleanBindings))
        );
    }

    /**
     * Compile a delete statement into SQL.
	 * 编译delete语句成SQL
     *
     * @param  \Illuminate\Database\Query\Builder  $query
     * @return string
     */
    public function compileDelete(Builder $query)
    {
        if (isset($query->joins) || isset($query->limit)) {
            return $this->compileDeleteWithJoinsOrLimit($query);
        }

        return parent::compileDelete($query);
    }

    /**
     * Compile a delete statement with joins or limit into SQL.
	 * 编译带有连接或限制的删除语句成SQL
     *
     * @param  \Illuminate\Database\Query\Builder  $query
     * @return string
     */
    protected function compileDeleteWithJoinsOrLimit(Builder $query)
    {
        $table = $this->wrapTable($query->from);

        $alias = last(preg_split('/\s+as\s+/i', $query->from));

        $selectSql = $this->compileSelect($query->select($alias.'.rowid'));

        return "delete from {$table} where {$this->wrap('rowid')} in ({$selectSql})";
    }

    /**
     * Compile a truncate table statement into SQL.
	 * 编译截断表语句成SQL
     *
     * @param  \Illuminate\Database\Query\Builder  $query
     * @return array
     */
    public function compileTruncate(Builder $query)
    {
        return [
            'delete from sqlite_sequence where name = ?' => [$query->from],
            'delete from '.$this->wrapTable($query->from) => [],
        ];
    }

    /**
     * Wrap the given JSON selector.
	 * 包装给定的JSON选择器
     *
     * @param  string  $value
     * @return string
     */
    protected function wrapJsonSelector($value)
    {
        [$field, $path] = $this->wrapJsonFieldAndPath($value);

        return 'json_extract('.$field.$path.')';
    }
}

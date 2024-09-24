<?php
/**
 * 数据库，SqlServer语法
 */

namespace Illuminate\Database\Query\Grammars;

use Illuminate\Database\Query\Builder;
use Illuminate\Support\Arr;

class SqlServerGrammar extends Grammar
{
    /**
     * All of the available clause operators.
	 * 所有可用子句操作符
     *
     * @var array
     */
    protected $operators = [
        '=', '<', '>', '<=', '>=', '!<', '!>', '<>', '!=',
        'like', 'not like', 'ilike',
        '&', '&=', '|', '|=', '^', '^=',
    ];

    /**
     * Compile a select query into SQL.
	 *  编译select查询语句成SQL
     *
     * @param  \Illuminate\Database\Query\Builder  $query
     * @return string
     */
    public function compileSelect(Builder $query)
    {
        if (! $query->offset) {
            return parent::compileSelect($query);
        }

        // If an offset is present on the query, we will need to wrap the query in
        // a big "ANSI" offset syntax block. This is very nasty compared to the
        // other database systems but is necessary for implementing features.
		// 如果查询上存在偏移，我们需要将查询包装在一个大的“ANSI”偏移语法块中。
		// 与其他数据库系统相比，这非常糟糕，但对于实现功能是必要的。
        if (is_null($query->columns)) {
            $query->columns = ['*'];
        }

        return $this->compileAnsiOffset(
            $query, $this->compileComponents($query)
        );
    }

    /**
     * Compile the "select *" portion of the query.
	 * 编译查询的"select *"部分
     *
     * @param  \Illuminate\Database\Query\Builder  $query
     * @param  array  $columns
     * @return string|null
     */
    protected function compileColumns(Builder $query, $columns)
    {
        if (! is_null($query->aggregate)) {
            return;
        }

        $select = $query->distinct ? 'select distinct ' : 'select ';

        // If there is a limit on the query, but not an offset, we will add the top
        // clause to the query, which serves as a "limit" type clause within the
        // SQL Server system similar to the limit keywords available in MySQL.
		// 如果查询有限制，但没有偏移，我们将在查询中添加top子句，
		// 它在SQL Server系统中充当“limit”类型子句，类似于MySQL中可用的limit关键字。
        if (is_numeric($query->limit) && $query->limit > 0 && $query->offset <= 0) {
            $select .= 'top '.((int) $query->limit).' ';
        }

        return $select.$this->columnize($columns);
    }

    /**
     * Compile the "from" portion of the query.
	 * 编译查询的"from"部分
     *
     * @param  \Illuminate\Database\Query\Builder  $query
     * @param  string  $table
     * @return string
     */
    protected function compileFrom(Builder $query, $table)
    {
        $from = parent::compileFrom($query, $table);

        if (is_string($query->lock)) {
            return $from.' '.$query->lock;
        }

        if (! is_null($query->lock)) {
            return $from.' with(rowlock,'.($query->lock ? 'updlock,' : '').'holdlock)';
        }

        return $from;
    }

    /**
     * Compile a "where date" clause.
	 *  编译"where date"子句
     *
     * @param  \Illuminate\Database\Query\Builder  $query
     * @param  array  $where
     * @return string
     */
    protected function whereDate(Builder $query, $where)
    {
        $value = $this->parameter($where['value']);

        return 'cast('.$this->wrap($where['column']).' as date) '.$where['operator'].' '.$value;
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
        $value = $this->parameter($where['value']);

        return 'cast('.$this->wrap($where['column']).' as time) '.$where['operator'].' '.$value;
    }

    /**
     * Compile a "JSON contains" statement into SQL.
	 * 编译"JSON contains"子句至SQL
     *
     * @param  string  $column
     * @param  string  $value
     * @return string
     */
    protected function compileJsonContains($column, $value)
    {
        [$field, $path] = $this->wrapJsonFieldAndPath($column);

        return $value.' in (select [value] from openjson('.$field.$path.'))';
    }

    /**
     * Prepare the binding for a "JSON contains" statement.
	 * 准备绑定"JSON contains"语句
     *
     * @param  mixed  $binding
     * @return string
     */
    public function prepareBindingForJsonContains($binding)
    {
        return is_bool($binding) ? json_encode($binding) : $binding;
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

        return '(select count(*) from openjson('.$field.$path.')) '.$operator.' '.$value;
    }

    /**
     * Create a full ANSI offset clause for the query.
	 * 创建一个完整的ANSI偏移子句为查询
     *
     * @param  \Illuminate\Database\Query\Builder  $query
     * @param  array  $components
     * @return string
     */
    protected function compileAnsiOffset(Builder $query, $components)
    {
        // An ORDER BY clause is required to make this offset query work, so if one does
        // not exist we'll just create a dummy clause to trick the database and so it
        // does not complain about the queries for not having an "order by" clause.
		// ORDER BY子句是使此偏移查询工作所必需的，因此如果不存在，
		// 我们将创建一个虚拟子句来欺骗数据库，这样它就不会因为没有"order by"子句而抱怨查询。
        if (empty($components['orders'])) {
            $components['orders'] = 'order by (select 0)';
        }

        // We need to add the row number to the query so we can compare it to the offset
        // and limit values given for the statements. So we will add an expression to
        // the "select" that will give back the row numbers on each of the records.
		// 我们需要将行号添加到查询中，以便将其与为语句给定的偏移量和限制值进行比较。
		// 因此，我们将在"select"中添加一个表达式，该表达式将返回每条记录上的行号。
        $components['columns'] .= $this->compileOver($components['orders']);

        unset($components['orders']);

        // Next we need to calculate the constraints that should be placed on the query
        // to get the right offset and limit from our query but if there is no limit
        // set we will just handle the offset only since that is all that matters.
		// 接下来，我们需要计算应该对查询施加的约束，以便从查询中获得正确的偏移量和限制，
		// 但如果没有设置限制，我们将只处理偏移量，因为这才是最重要的。
        $sql = $this->concatenate($components);

        return $this->compileTableExpression($sql, $query);
    }

    /**
     * Compile the over statement for a table expression.
	 * 编译表表达式的over语句
     *
     * @param  string  $orderings
     * @return string
     */
    protected function compileOver($orderings)
    {
        return ", row_number() over ({$orderings}) as row_num";
    }

    /**
     * Compile a common table expression for a query.
	 * 编译公共表表达式为查询
     *
     * @param  string  $sql
     * @param  \Illuminate\Database\Query\Builder  $query
     * @return string
     */
    protected function compileTableExpression($sql, $query)
    {
        $constraint = $this->compileRowConstraint($query);

        return "select * from ({$sql}) as temp_table where row_num {$constraint} order by row_num";
    }

    /**
     * Compile the limit / offset row constraint for a query.
	 * 编译限制/偏移行约束为查询
     *
     * @param  \Illuminate\Database\Query\Builder  $query
     * @return string
     */
    protected function compileRowConstraint($query)
    {
        $start = (int) $query->offset + 1;

        if ($query->limit > 0) {
            $finish = (int) $query->offset + (int) $query->limit;

            return "between {$start} and {$finish}";
        }

        return ">= {$start}";
    }

    /**
     * Compile the random statement into SQL.
	 * 编译随机语句成SQL
     *
     * @param  string  $seed
     * @return string
     */
    public function compileRandom($seed)
    {
        return 'NEWID()';
    }

    /**
     * Compile the "limit" portions of the query.
	 * 编译查询的"limit"部分
     *
     * @param  \Illuminate\Database\Query\Builder  $query
     * @param  int  $limit
     * @return string
     */
    protected function compileLimit(Builder $query, $limit)
    {
        return '';
    }

    /**
     * Compile the "offset" portions of the query.
	 * 编译查询的"偏移"部分
     *
     * @param  \Illuminate\Database\Query\Builder  $query
     * @param  int  $offset
     * @return string
     */
    protected function compileOffset(Builder $query, $offset)
    {
        return '';
    }

    /**
     * Compile the lock into SQL.
	 * 编译锁成SQ
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
        return 'select * from ('.$sql.') as '.$this->wrapTable('temp_table');
    }

    /**
     * Compile an exists statement into SQL.
	 * 编译exists语句成SQL
     *
     * @param  \Illuminate\Database\Query\Builder  $query
     * @return string
     */
    public function compileExists(Builder $query)
    {
        $existsQuery = clone $query;

        $existsQuery->columns = [];

        return $this->compileSelect($existsQuery->selectRaw('1 [exists]')->limit(1));
    }

    /**
     * Compile an update statement with joins into SQL.
	 * 编译带有连接的更新语句成SQL
     *
     * @param  \Illuminate\Database\Query\Builder  $query
     * @param  string  $table
     * @param  string  $columns
     * @param  string  $where
     * @return string
     */
    protected function compileUpdateWithJoins(Builder $query, $table, $columns, $where)
    {
        $alias = last(explode(' as ', $table));

        $joins = $this->compileJoins($query, $query->joins);

        return "update {$alias} set {$columns} from {$table} {$joins} {$where}";
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
        $cleanBindings = Arr::except($bindings, 'select');

        return array_values(
            array_merge($values, Arr::flatten($cleanBindings))
        );
    }

    /**
     * Compile the SQL statement to define a savepoint.
	 * 编译SQL语句以定义保存点
     *
     * @param  string  $name
     * @return string
     */
    public function compileSavepoint($name)
    {
        return 'SAVE TRANSACTION '.$name;
    }

    /**
     * Compile the SQL statement to execute a savepoint rollback.
	 * 编译SQL语句以执行保存点回滚
     *
     * @param  string  $name
     * @return string
     */
    public function compileSavepointRollBack($name)
    {
        return 'ROLLBACK TRANSACTION '.$name;
    }

    /**
     * Get the format for database stored dates.
	 * 得到数据库存储日期的格式
     *
     * @return string
     */
    public function getDateFormat()
    {
        return 'Y-m-d H:i:s.v';
    }

    /**
     * Wrap a single string in keyword identifiers.
	 *包装单个字符串在关键字标识符中
     *
     * @param  string  $value
     * @return string
     */
    protected function wrapValue($value)
    {
        return $value === '*' ? $value : '['.str_replace(']', ']]', $value).']';
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

        return 'json_value('.$field.$path.')';
    }

    /**
     * Wrap the given JSON boolean value.
	 * 包装给定的JSON布尔值
     *
     * @param  string  $value
     * @return string
     */
    protected function wrapJsonBooleanValue($value)
    {
        return "'".$value."'";
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
            return $this->wrapTableValuedFunction(parent::wrapTable($table));
        }

        return $this->getValue($table);
    }

    /**
     * Wrap a table in keyword identifiers.
	 * 包装表用关键字标识符
     *
     * @param  string  $table
     * @return string
     */
    protected function wrapTableValuedFunction($table)
    {
        if (preg_match('/^(.+?)(\(.*?\))]$/', $table, $matches) === 1) {
            $table = $matches[1].']'.$matches[2];
        }

        return $table;
    }
}

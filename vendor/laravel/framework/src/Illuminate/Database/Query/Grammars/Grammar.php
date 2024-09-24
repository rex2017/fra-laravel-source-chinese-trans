<?php
/**
 * 数据库，查询语法
 */

namespace Illuminate\Database\Query\Grammars;

use Illuminate\Database\Grammar as BaseGrammar;
use Illuminate\Database\Query\Builder;
use Illuminate\Database\Query\JoinClause;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use RuntimeException;

class Grammar extends BaseGrammar
{
    /**
     * The grammar specific operators.
	 * 语法特定操作符
     *
     * @var array
     */
    protected $operators = [];

    /**
     * The components that make up a select clause.
	 * 组成select子句的组件, 如columsn,wheres等
     *
     * @var array
     */
    protected $selectComponents = [
        'aggregate',
        'columns',
        'from',
        'joins',
        'wheres',
        'groups',
        'havings',
        'orders',
        'limit',
        'offset',
        'lock',
    ];

    /**
     * Compile a select query into SQL.
	 * 编译select查询语句成SQL
     *
     * @param  \Illuminate\Database\Query\Builder  $query
     * @return string
     */
    public function compileSelect(Builder $query)
    {
        if ($query->unions && $query->aggregate) {
            return $this->compileUnionAggregate($query);
        }

        // If the query does not have any columns set, we'll set the columns to the
        // * character to just get all of the columns from the database. Then we
        // can build the query and concatenate all the pieces together as one.
		// 如果查询没有设置任何列，我们将列设置为*字符，仅从数据库中获取所有列。
		// 然后我们可以构建查询并将所有部分连接在一起。
        $original = $query->columns;

        if (is_null($query->columns)) {
            $query->columns = ['*'];
        }

        // To compile the query, we'll spin through each component of the query and
        // see if that component exists. If it does we'll just call the compiler
        // function for the component which is responsible for making the SQL.
		// 为了编译查询，我们将遍历查询的每个组件查看是否存在。
		// 如果是这样我们将调用编译器负责生成SQL的组件函数。
        $sql = trim($this->concatenate(
            $this->compileComponents($query))
        );

        if ($query->unions) {
            $sql = $this->wrapUnion($sql).' '.$this->compileUnions($query);
        }

        $query->columns = $original;

        return $sql;
    }

    /**
     * Compile the components necessary for a select clause.
	 * 编译查询语句所需的组件
     *
     * @param  \Illuminate\Database\Query\Builder  $query
     * @return array
     */
    protected function compileComponents(Builder $query)
    {
        $sql = [];

        foreach ($this->selectComponents as $component) {
            // To compile the query, we'll spin through each component of the query and
            // see if that component exists. If it does we'll just call the compiler
            // function for the component which is responsible for making the SQL.
			// 为了编译查询，我们将遍历查询的每个组件查看是否存在。
			// 如果是这样我们将调用编译器负责生成SQL的组件函数。
            if (isset($query->$component) && ! is_null($query->$component)) {
                $method = 'compile'.ucfirst($component);

                $sql[$component] = $this->$method($query, $query->$component);
            }
        }

        return $sql;
    }

    /**
     * Compile an aggregated select clause.
	 * 编译聚合选择子句
     *
     * @param  \Illuminate\Database\Query\Builder  $query
     * @param  array  $aggregate
     * @return string
     */
    protected function compileAggregate(Builder $query, $aggregate)
    {
        $column = $this->columnize($aggregate['columns']);

        // If the query has a "distinct" constraint and we're not asking for all columns
        // we need to prepend "distinct" onto the column name so that the query takes
        // it into account when it performs the aggregating operations on the data.
		// 如果查询具有"distinct"约束，并且我们没有要求所有列，
		// 我们需要在列名前添加"distince"，以便查询在对数据执行聚合操作时将其考虑在内。
        if (is_array($query->distinct)) {
            $column = 'distinct '.$this->columnize($query->distinct);
        } elseif ($query->distinct && $column !== '*') {
            $column = 'distinct '.$column;
        }

        return 'select '.$aggregate['function'].'('.$column.') as aggregate';
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
        // If the query is actually performing an aggregating select, we will let that
        // compiler handle the building of the select clauses, as it will need some
        // more syntax that is best handled by that function to keep things neat.
		// 如果查询实际上正在执行聚合选择，我们将让编译器处理选择子句的构建，
		// 因为它需要更多语法，最好由该函数处理，以保持整洁。
        if (! is_null($query->aggregate)) {
            return;
        }

        if ($query->distinct) {
            $select = 'select distinct ';
        } else {
            $select = 'select ';
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
        return 'from '.$this->wrapTable($table);
    }

    /**
     * Compile the "join" portions of the query.
	 * 编译查询的"联接"部分
     *
     * @param  \Illuminate\Database\Query\Builder  $query
     * @param  array  $joins
     * @return string
     */
    protected function compileJoins(Builder $query, $joins)
    {
        return collect($joins)->map(function ($join) use ($query) {
            $table = $this->wrapTable($join->table);

            $nestedJoins = is_null($join->joins) ? '' : ' '.$this->compileJoins($query, $join->joins);

            $tableAndNestedJoins = is_null($join->joins) ? $table : '('.$table.$nestedJoins.')';

            return trim("{$join->type} join {$tableAndNestedJoins} {$this->compileWheres($join)}");
        })->implode(' ');
    }

    /**
     * Compile the "where" portions of the query.
	 * 编译查询的"where"部分
     *
     * @param  \Illuminate\Database\Query\Builder  $query
     * @return string
     */
    protected function compileWheres(Builder $query)
    {
        // Each type of where clauses has its own compiler function which is responsible
        // for actually creating the where clauses SQL. This helps keep the code nice
        // and maintainable since each clause has a very small method that it uses.
		// 每种where子句都有自己的编译器函数，负责实际创建where子句SQL。
		// 这有助于保持代码的美观和可维护性，因为每个子句都有一个非常小的方法。
        if (is_null($query->wheres)) {
            return '';
        }

        // If we actually have some where clauses, we will strip off the first boolean
        // operator, which is added by the query builders for convenience so we can
        // avoid checking for the first clauses in each of the compilers methods.
		// 如果我们真的有一些where子句，我们将去掉第一个布尔运算符，
		// 它是由查询构建器为了方便而添加的，这样我们就可以避免在每个编译器方法中检查第一个子句。
        if (count($sql = $this->compileWheresToArray($query)) > 0) {
            return $this->concatenateWhereClauses($query, $sql);
        }

        return '';
    }

    /**
     * Get an array of all the where clauses for the query.
	 * 查询的所有条件子句的数组
     *
     * @param  \Illuminate\Database\Query\Builder  $query
     * @return array
     */
    protected function compileWheresToArray($query)
    {
        return collect($query->wheres)->map(function ($where) use ($query) {
            return $where['boolean'].' '.$this->{"where{$where['type']}"}($query, $where);
        })->all();
    }

    /**
     * Format the where clause statements into one string.
	 * 将where子句语句格式化为一个字符串
     *
     * @param  \Illuminate\Database\Query\Builder  $query
     * @param  array  $sql
     * @return string
     */
    protected function concatenateWhereClauses($query, $sql)
    {
        $conjunction = $query instanceof JoinClause ? 'on' : 'where';

        return $conjunction.' '.$this->removeLeadingBoolean(implode(' ', $sql));
    }

    /**
     * Compile a raw where clause.
	 * 编译原始条件子句
     *
     * @param  \Illuminate\Database\Query\Builder  $query
     * @param  array  $where
     * @return string
     */
    protected function whereRaw(Builder $query, $where)
    {
        return $where['sql'];
    }

    /**
     * Compile a basic where clause.
	 * 编译基本条件子句
     *
     * @param  \Illuminate\Database\Query\Builder  $query
     * @param  array  $where
     * @return string
     */
    protected function whereBasic(Builder $query, $where)
    {
        $value = $this->parameter($where['value']);

        return $this->wrap($where['column']).' '.$where['operator'].' '.$value;
    }

    /**
     * Compile a "where in" clause.
	 * 编写"where in"子句
     *
     * @param  \Illuminate\Database\Query\Builder  $query
     * @param  array  $where
     * @return string
     */
    protected function whereIn(Builder $query, $where)
    {
        if (! empty($where['values'])) {
            return $this->wrap($where['column']).' in ('.$this->parameterize($where['values']).')';
        }

        return '0 = 1';
    }

    /**
     * Compile a "where not in" clause.
	 * 编译"where not in"子句
     *
     * @param  \Illuminate\Database\Query\Builder  $query
     * @param  array  $where
     * @return string
     */
    protected function whereNotIn(Builder $query, $where)
    {
        if (! empty($where['values'])) {
            return $this->wrap($where['column']).' not in ('.$this->parameterize($where['values']).')';
        }

        return '1 = 1';
    }

    /**
     * Compile a "where not in raw" clause.
	 * 编译"where not in raw"子句
     *
     * For safety, whereIntegerInRaw ensures this method is only used with integer values.
     *
     * @param  \Illuminate\Database\Query\Builder  $query
     * @param  array  $where
     * @return string
     */
    protected function whereNotInRaw(Builder $query, $where)
    {
        if (! empty($where['values'])) {
            return $this->wrap($where['column']).' not in ('.implode(', ', $where['values']).')';
        }

        return '1 = 1';
    }

    /**
     * Compile a "where in raw" clause.
	 *编译"where in raw"子句
     *
     * For safety, whereIntegerInRaw ensures this method is only used with integer values.
     *
     * @param  \Illuminate\Database\Query\Builder  $query
     * @param  array  $where
     * @return string
     */
    protected function whereInRaw(Builder $query, $where)
    {
        if (! empty($where['values'])) {
            return $this->wrap($where['column']).' in ('.implode(', ', $where['values']).')';
        }

        return '0 = 1';
    }

    /**
     * Compile a "where null" clause.
	 * 编译"where null"子句
     *
     * @param  \Illuminate\Database\Query\Builder  $query
     * @param  array  $where
     * @return string
     */
    protected function whereNull(Builder $query, $where)
    {
        return $this->wrap($where['column']).' is null';
    }

    /**
     * Compile a "where not null" clause.
	 * 编译"where not null子句
     *
     * @param  \Illuminate\Database\Query\Builder  $query
     * @param  array  $where
     * @return string
     */
    protected function whereNotNull(Builder $query, $where)
    {
        return $this->wrap($where['column']).' is not null';
    }

    /**
     * Compile a "between" where clause.
	 * 编译"between"子句
     *
     * @param  \Illuminate\Database\Query\Builder  $query
     * @param  array  $where
     * @return string
     */
    protected function whereBetween(Builder $query, $where)
    {
        $between = $where['not'] ? 'not between' : 'between';

        $min = $this->parameter(reset($where['values']));

        $max = $this->parameter(end($where['values']));

        return $this->wrap($where['column']).' '.$between.' '.$min.' and '.$max;
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
        return $this->dateBasedWhere('date', $query, $where);
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
        return $this->dateBasedWhere('time', $query, $where);
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
        return $this->dateBasedWhere('day', $query, $where);
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
        return $this->dateBasedWhere('month', $query, $where);
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
        return $this->dateBasedWhere('year', $query, $where);
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

        return $type.'('.$this->wrap($where['column']).') '.$where['operator'].' '.$value;
    }

    /**
     * Compile a where clause comparing two columns..
	 * 编译一个比较两列的where子句
     *
     * @param  \Illuminate\Database\Query\Builder  $query
     * @param  array  $where
     * @return string
     */
    protected function whereColumn(Builder $query, $where)
    {
        return $this->wrap($where['first']).' '.$where['operator'].' '.$this->wrap($where['second']);
    }

    /**
     * Compile a nested where clause.
	 * 编译嵌套的where子句
     *
     * @param  \Illuminate\Database\Query\Builder  $query
     * @param  array  $where
     * @return string
     */
    protected function whereNested(Builder $query, $where)
    {
        // Here we will calculate what portion of the string we need to remove. If this
        // is a join clause query, we need to remove the "on" portion of the SQL and
        // if it is a normal query we need to take the leading "where" of queries.
		// 在这里，我们将计算需要删除字符串的哪一部分。
		// 如果这是一个连接子句查询，我们需要删除SQL的"on"部分，如果这是正常查询，
		// 我们则需要取查询的前导"where"。
        $offset = $query instanceof JoinClause ? 3 : 6;

        return '('.substr($this->compileWheres($where['query']), $offset).')';
    }

    /**
     * Compile a where condition with a sub-select.
	 * 编译带有子选择的where条件
     *
     * @param  \Illuminate\Database\Query\Builder  $query
     * @param  array  $where
     * @return string
     */
    protected function whereSub(Builder $query, $where)
    {
        $select = $this->compileSelect($where['query']);

        return $this->wrap($where['column']).' '.$where['operator']." ($select)";
    }

    /**
     * Compile a where exists clause.
	 * 编译where exists子句
     *
     * @param  \Illuminate\Database\Query\Builder  $query
     * @param  array  $where
     * @return string
     */
    protected function whereExists(Builder $query, $where)
    {
        return 'exists ('.$this->compileSelect($where['query']).')';
    }

    /**
     * Compile a where exists clause.
	 * 编译where exists子句
     *
     * @param  \Illuminate\Database\Query\Builder  $query
     * @param  array  $where
     * @return string
     */
    protected function whereNotExists(Builder $query, $where)
    {
        return 'not exists ('.$this->compileSelect($where['query']).')';
    }

    /**
     * Compile a where row values condition.
	 * 编译where行值条件
     *
     * @param  \Illuminate\Database\Query\Builder  $query
     * @param  array  $where
     * @return string
     */
    protected function whereRowValues(Builder $query, $where)
    {
        $columns = $this->columnize($where['columns']);

        $values = $this->parameterize($where['values']);

        return '('.$columns.') '.$where['operator'].' ('.$values.')';
    }

    /**
     * Compile a "where JSON boolean" clause.
	 * 编译"where JSON boolean"子句
     *
     * @param  \Illuminate\Database\Query\Builder  $query
     * @param  array  $where
     * @return string
     */
    protected function whereJsonBoolean(Builder $query, $where)
    {
        $column = $this->wrapJsonBooleanSelector($where['column']);

        $value = $this->wrapJsonBooleanValue(
            $this->parameter($where['value'])
        );

        return $column.' '.$where['operator'].' '.$value;
    }

    /**
     * Compile a "where JSON contains" clause.
	 * 编译"where JSON contains"子句
     *
     * @param  \Illuminate\Database\Query\Builder  $query
     * @param  array  $where
     * @return string
     */
    protected function whereJsonContains(Builder $query, $where)
    {
        $not = $where['not'] ? 'not ' : '';

        return $not.$this->compileJsonContains(
            $where['column'], $this->parameter($where['value'])
        );
    }

    /**
     * Compile a "JSON contains" statement into SQL.
	 * 编译"JSON contains"子句至SQL
     *
     * @param  string  $column
     * @param  string  $value
     * @return string
     *
     * @throws \RuntimeException
     */
    protected function compileJsonContains($column, $value)
    {
        throw new RuntimeException('This database engine does not support JSON contains operations.');
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
        return json_encode($binding);
    }

    /**
     * Compile a "where JSON length" clause.
	 * 编译"where JSON length"子句
     *
     * @param  \Illuminate\Database\Query\Builder  $query
     * @param  array  $where
     * @return string
     */
    protected function whereJsonLength(Builder $query, $where)
    {
        return $this->compileJsonLength(
            $where['column'], $where['operator'], $this->parameter($where['value'])
        );
    }

    /**
     * Compile a "JSON length" statement into SQL.
	 * 编译"JSON length"子句至SQL
     *
     * @param  string  $column
     * @param  string  $operator
     * @param  string  $value
     * @return string
     *
     * @throws \RuntimeException
     */
    protected function compileJsonLength($column, $operator, $value)
    {
        throw new RuntimeException('This database engine does not support JSON length operations.');
    }

    /**
     * Compile the "group by" portions of the query.
	 * 编译查询的"group by"部分
     *
     * @param  \Illuminate\Database\Query\Builder  $query
     * @param  array  $groups
     * @return string
     */
    protected function compileGroups(Builder $query, $groups)
    {
        return 'group by '.$this->columnize($groups);
    }

    /**
     * Compile the "having" portions of the query.
	 * 编译"having"查询的部分
	 * 
     *
     * @param  \Illuminate\Database\Query\Builder  $query
     * @param  array  $havings
     * @return string
     */
    protected function compileHavings(Builder $query, $havings)
    {
        $sql = implode(' ', array_map([$this, 'compileHaving'], $havings));

        return 'having '.$this->removeLeadingBoolean($sql);
    }

    /**
     * Compile a single having clause.
	 * 编译单个having子句
     *
     * @param  array  $having
     * @return string
     */
    protected function compileHaving(array $having)
    {
        // If the having clause is "raw", we can just return the clause straight away
        // without doing any more processing on it. Otherwise, we will compile the
        // clause into SQL based on the components that make it up from builder.
		// 如果have子句是"原始"的，我们可以直接返回子句，而无需对其进行任何处理。
		// 否则，我们将根据构建器中组成子句的组件将子句编译成SQL。
        if ($having['type'] === 'Raw') {
            return $having['boolean'].' '.$having['sql'];
        } elseif ($having['type'] === 'between') {
            return $this->compileHavingBetween($having);
        }

        return $this->compileBasicHaving($having);
    }

    /**
     * Compile a basic having clause.
	 * 编译基本having子句
     *
     * @param  array  $having
     * @return string
     */
    protected function compileBasicHaving($having)
    {
        $column = $this->wrap($having['column']);

        $parameter = $this->parameter($having['value']);

        return $having['boolean'].' '.$column.' '.$having['operator'].' '.$parameter;
    }

    /**
     * Compile a "between" having clause.
	 * 编译between子句
     *
     * @param  array  $having
     * @return string
     */
    protected function compileHavingBetween($having)
    {
        $between = $having['not'] ? 'not between' : 'between';

        $column = $this->wrap($having['column']);

        $min = $this->parameter(head($having['values']));

        $max = $this->parameter(last($having['values']));

        return $having['boolean'].' '.$column.' '.$between.' '.$min.' and '.$max;
    }

    /**
     * Compile the "order by" portions of the query.
	 * 编译order by查询部分
     *
     * @param  \Illuminate\Database\Query\Builder  $query
     * @param  array  $orders
     * @return string
     */
    protected function compileOrders(Builder $query, $orders)
    {
        if (! empty($orders)) {
            return 'order by '.implode(', ', $this->compileOrdersToArray($query, $orders));
        }

        return '';
    }

    /**
     * Compile the query orders to an array.
	 * 将查询顺序编译为数组
     *
     * @param  \Illuminate\Database\Query\Builder  $query
     * @param  array  $orders
     * @return array
     */
    protected function compileOrdersToArray(Builder $query, $orders)
    {
        return array_map(function ($order) {
            return $order['sql'] ?? $this->wrap($order['column']).' '.$order['direction'];
        }, $orders);
    }

    /**
     * Compile the random statement into SQL.
	 * 将随机语句编译成SQL
     *
     * @param  string  $seed
     * @return string
     */
    public function compileRandom($seed)
    {
        return 'RANDOM()';
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
        return 'limit '.(int) $limit;
    }

    /**
     * Compile the "offset" portions of the query.
	 * 编译查询的"offset"部分
     *
     * @param  \Illuminate\Database\Query\Builder  $query
     * @param  int  $offset
     * @return string
     */
    protected function compileOffset(Builder $query, $offset)
    {
        return 'offset '.(int) $offset;
    }

    /**
     * Compile the "union" queries attached to the main query.
	 * 编译附加到主查询的"union"查询
     *
     * @param  \Illuminate\Database\Query\Builder  $query
     * @return string
     */
    protected function compileUnions(Builder $query)
    {
        $sql = '';

        foreach ($query->unions as $union) {
            $sql .= $this->compileUnion($union);
        }

        if (! empty($query->unionOrders)) {
            $sql .= ' '.$this->compileOrders($query, $query->unionOrders);
        }

        if (isset($query->unionLimit)) {
            $sql .= ' '.$this->compileLimit($query, $query->unionLimit);
        }

        if (isset($query->unionOffset)) {
            $sql .= ' '.$this->compileOffset($query, $query->unionOffset);
        }

        return ltrim($sql);
    }

    /**
     * Compile a single union statement.
	 * 编译单个union查询
     *
     * @param  array  $union
     * @return string
     */
    protected function compileUnion(array $union)
    {
        $conjunction = $union['all'] ? ' union all ' : ' union ';

        return $conjunction.$this->wrapUnion($union['query']->toSql());
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
        return '('.$sql.')';
    }

    /**
     * Compile a union aggregate query into SQL.
	 * 编译联合聚合查询为SQL
     *
     * @param  \Illuminate\Database\Query\Builder  $query
     * @return string
     */
    protected function compileUnionAggregate(Builder $query)
    {
        $sql = $this->compileAggregate($query, $query->aggregate);

        $query->aggregate = null;

        return $sql.' from ('.$this->compileSelect($query).') as '.$this->wrapTable('temp_table');
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
        $select = $this->compileSelect($query);

        return "select exists({$select}) as {$this->wrap('exists')}";
    }

    /**
     * Compile an insert statement into SQL.
	 * 编译insert语句成SQL
     *
     * @param  \Illuminate\Database\Query\Builder  $query
     * @param  array  $values
     * @return string
     */
    public function compileInsert(Builder $query, array $values)
    {
        // Essentially we will force every insert to be treated as a batch insert which
        // simply makes creating the SQL easier for us since we can utilize the same
        // basic routine regardless of an amount of records given to us to insert.
		// 本质上，我们将强制将每个插入视为批插入，这只会使创建SQL对我们来说更容易，
		// 因为我们可以使用相同的基本例程，而不管给我们插入的记录数量如何。
        $table = $this->wrapTable($query->from);

        if (empty($values)) {
            return "insert into {$table} default values";
        }

        if (! is_array(reset($values))) {
            $values = [$values];
        }

        $columns = $this->columnize(array_keys(reset($values)));

        // We need to build a list of parameter place-holders of values that are bound
        // to the query. Each insert should have the exact same amount of parameter
        // bindings so we will loop through the record and parameterize them all.
		// 我们需要构建一个绑定到查询的值的参数占位符列表。
		// 每个插入都应该有完全相同数量的参数绑定，这样我们就可以遍历记录并将它们全部参数化。
        $parameters = collect($values)->map(function ($record) {
            return '('.$this->parameterize($record).')';
        })->implode(', ');

        return "insert into $table ($columns) values $parameters";
    }

    /**
     * Compile an insert ignore statement into SQL.
	 * 编译插入忽略语句成SQL
     *
     * @param  \Illuminate\Database\Query\Builder  $query
     * @param  array  $values
     * @return string
     *
     * @throws \RuntimeException
     */
    public function compileInsertOrIgnore(Builder $query, array $values)
    {
        throw new RuntimeException('This database engine does not support inserting while ignoring errors.');
    }

    /**
     * Compile an insert and get ID statement into SQL.
	 * 编译插入和获取ID语句成SQL
     *
     * @param  \Illuminate\Database\Query\Builder  $query
     * @param  array  $values
     * @param  string  $sequence
     * @return string
     */
    public function compileInsertGetId(Builder $query, $values, $sequence)
    {
        return $this->compileInsert($query, $values);
    }

    /**
     * Compile an insert statement using a subquery into SQL.
	 * 编译插入语句为SQL使用子查询
     *
     * @param  \Illuminate\Database\Query\Builder  $query
     * @param  array  $columns
     * @param  string  $sql
     * @return string
     */
    public function compileInsertUsing(Builder $query, array $columns, string $sql)
    {
        return "insert into {$this->wrapTable($query->from)} ({$this->columnize($columns)}) $sql";
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
        $table = $this->wrapTable($query->from);

        $columns = $this->compileUpdateColumns($query, $values);

        $where = $this->compileWheres($query);

        return trim(
            isset($query->joins)
                ? $this->compileUpdateWithJoins($query, $table, $columns, $where)
                : $this->compileUpdateWithoutJoins($query, $table, $columns, $where)
        );
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
        return collect($values)->map(function ($value, $key) {
            return $this->wrap($key).' = '.$this->parameter($value);
        })->implode(', ');
    }

    /**
     * Compile an update statement without joins into SQL.
	  * 编译一个没有连接的update语句到SQL中
     *
     * @param  \Illuminate\Database\Query\Builder  $query
     * @param  string  $table
     * @param  string  $columns
     * @param  string  $where
     * @return string
     */
    protected function compileUpdateWithoutJoins(Builder $query, $table, $columns, $where)
    {
        return "update {$table} set {$columns} {$where}";
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
        $joins = $this->compileJoins($query, $query->joins);

        return "update {$table} {$joins} set {$columns} {$where}";
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
        $cleanBindings = Arr::except($bindings, ['select', 'join']);

        return array_values(
            array_merge($bindings['join'], $values, Arr::flatten($cleanBindings))
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
        $table = $this->wrapTable($query->from);

        $where = $this->compileWheres($query);

        return trim(
            isset($query->joins)
                ? $this->compileDeleteWithJoins($query, $table, $where)
                : $this->compileDeleteWithoutJoins($query, $table, $where)
        );
    }

    /**
     * Compile a delete statement without joins into SQL.
	 * 编译没有连接的delete语句到SQL中
     *
     * @param  \Illuminate\Database\Query\Builder  $query
     * @param  string  $table
     * @param  string  $where
     * @return string
     */
    protected function compileDeleteWithoutJoins(Builder $query, $table, $where)
    {
        return "delete from {$table} {$where}";
    }

    /**
     * Compile a delete statement with joins into SQL.
	 * 编译带有连接的删除语句编成SQL
     *
     * @param  \Illuminate\Database\Query\Builder  $query
     * @param  string  $table
     * @param  string  $where
     * @return string
     */
    protected function compileDeleteWithJoins(Builder $query, $table, $where)
    {
        $alias = last(explode(' as ', $table));

        $joins = $this->compileJoins($query, $query->joins);

        return "delete {$alias} from {$table} {$joins} {$where}";
    }

    /**
     * Prepare the bindings for a delete statement.
	 * 为delete语句准备绑定
     *
     * @param  array  $bindings
     * @return array
     */
    public function prepareBindingsForDelete(array $bindings)
    {
        return Arr::flatten(
            Arr::except($bindings, 'select')
        );
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
        return ['truncate table '.$this->wrapTable($query->from) => []];
    }

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
        return is_string($value) ? $value : '';
    }

    /**
     * Determine if the grammar supports savepoints.
	 * 确定语法是否支持保存点
     *
     * @return bool
     */
    public function supportsSavepoints()
    {
        return true;
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
        return 'SAVEPOINT '.$name;
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
        return 'ROLLBACK TO SAVEPOINT '.$name;
    }

    /**
     * Wrap a value in keyword identifiers.
	 * 将值包装在关键字标识符中
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
		// 如果要包装的值有一个列别名，我们需要将这些部分分开，
		// 这样我们就可以单独包装表达式的每个段，然后使用"as"连接器将它们连接在一起。
        if (stripos($value, ' as ') !== false) {
            return $this->wrapAliasedValue($value, $prefixAlias);
        }

        // If the given value is a JSON selector we will wrap it differently than a
        // traditional value. We will need to split this path and wrap each part
        // wrapped, etc. Otherwise, we will simply wrap the value as a string.
		// 如果给定的值是JSON选择器，我们将以不同于传统值的方式包装它。
		// 我们需要分割此路径并将每个部分包装起来，等等。否则，我们只需将值包装成字符串。
        if ($this->isJsonSelector($value)) {
            return $this->wrapJsonSelector($value);
        }

        return $this->wrapSegments(explode('.', $value));
    }

    /**
     * Wrap the given JSON selector.
	 * 包装给定的JSON选择器
     *
     * @param  string  $value
     * @return string
     *
     * @throws \RuntimeException
     */
    protected function wrapJsonSelector($value)
    {
        throw new RuntimeException('This database engine does not support JSON operations.');
    }

    /**
     * Wrap the given JSON selector for boolean values.
	 * 包装给定的JSON选择器为布尔值
     *
     * @param  string  $value
     * @return string
     */
    protected function wrapJsonBooleanSelector($value)
    {
        return $this->wrapJsonSelector($value);
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
        return $value;
    }

    /**
     * Split the given JSON selector into the field and the optional path and wrap them separately.
	 * 将给定的JSON选择器拆分到字段和可选路径中，并分别包装它们。
     *
     * @param  string  $column
     * @return array
     */
    protected function wrapJsonFieldAndPath($column)
    {
        $parts = explode('->', $column, 2);

        $field = $this->wrap($parts[0]);

        $path = count($parts) > 1 ? ', '.$this->wrapJsonPath($parts[1], '->') : '';

        return [$field, $path];
    }

    /**
     * Wrap the given JSON path.
	 * 包装给定的JSON路径
     *
     * @param  string  $value
     * @param  string  $delimiter
     * @return string
     */
    protected function wrapJsonPath($value, $delimiter = '->')
    {
        $value = preg_replace("/([\\\\]+)?\\'/", "''", $value);

        return '\'$."'.str_replace($delimiter, '"."', $value).'"\'';
    }

    /**
     * Determine if the given string is a JSON selector.
	 * 确定给定字符串是否是JSON选择器
     *
     * @param  string  $value
     * @return bool
     */
    protected function isJsonSelector($value)
    {
        return Str::contains($value, '->');
    }

    /**
     * Concatenate an array of segments, removing empties.
	 * 连接一个段数组，删除空段。
     *
     * @param  array  $segments
     * @return string
     */
    protected function concatenate($segments)
    {
        return implode(' ', array_filter($segments, function ($value) {
            return (string) $value !== '';
        }));
    }

    /**
     * Remove the leading boolean from a statement.
	 * 删除前导布尔值从语句中
     *
     * @param  string  $value
     * @return string
     */
    protected function removeLeadingBoolean($value)
    {
        return preg_replace('/and |or /i', '', $value, 1);
    }

    /**
     * Get the grammar specific operators.
	 * 得到特定语法操作符
     *
     * @return array
     */
    public function getOperators()
    {
        return $this->operators;
    }
}

<?php
/**
 * 数据库，查询构建器，用于构建底层的SQL查询
 */

namespace Illuminate\Database\Query;

use Closure;
use DateTimeInterface;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Database\Concerns\BuildsQueries;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Query\Grammars\Grammar;
use Illuminate\Database\Query\Processors\Processor;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\LazyCollection;
use Illuminate\Support\Str;
use Illuminate\Support\Traits\ForwardsCalls;
use Illuminate\Support\Traits\Macroable;
use InvalidArgumentException;
use RuntimeException;

class Builder
{
    use BuildsQueries, ForwardsCalls, Macroable {
        __call as macroCall;
    }

    /**
     * The database connection instance.
	 * 数据库连接实例
     *
     * @var \Illuminate\Database\ConnectionInterface
     */
    public $connection;

    /**
     * The database query grammar instance.
	 * 数据库查询语法实例
     *
     * @var \Illuminate\Database\Query\Grammars\Grammar
     */
    public $grammar;

    /**
     * The database query post processor instance.
	 * 数据库查询请求进程实例
     *
     * @var \Illuminate\Database\Query\Processors\Processor
     */
    public $processor;

    /**
     * The current query value bindings.
	 * 当前查询值绑定，如select,from,where等
     *
     * @var array
     */
    public $bindings = [
        'select' => [],
        'from' => [],
        'join' => [],
        'where' => [],
        'groupBy' => [],
        'having' => [],
        'order' => [],
        'union' => [],
        'unionOrder' => [],
    ];

    /**
     * An aggregate function and column to be run.
	 * 要运行的聚合函数和列
     *
     * @var array
     */
    public $aggregate;

    /**
     * The columns that should be returned.
	 * 应该返回的列
     *
     * @var array
     */
    public $columns;

    /**
     * Indicates if the query returns distinct results.
	 * 指明查询是否返回不同的结果
     *
     * Occasionally contains the columns that should be distinct.
     *
     * @var bool|array
     */
    public $distinct = false;

    /**
     * The table which the query is targeting.
	 * 查询所针对的表
     *
     * @var string
     */
    public $from;

    /**
     * The table joins for the query.
	 * 连接查询表
     *
     * @var array
     */
    public $joins;

    /**
     * The where constraints for the query.
	 * 查询的where条件约束
     *
     * @var array
     */
    public $wheres = [];

    /**
     * The groupings for the query.
	 * 查询的group分组
     *
     * @var array
     */
    public $groups;

    /**
     * The having constraints for the query.
	 * 查询的having约束
     *
     * @var array
     */
    public $havings;

    /**
     * The orderings for the query.
	 * 查询的order排序
     *
     * @var array
     */
    public $orders;

    /**
     * The maximum number of records to return.
	 * 最大记录返回条数
     *
     * @var int
     */
    public $limit;

    /**
     * The number of records to skip.
	 * 要跳过的记录数
     *
     * @var int
     */
    public $offset;

    /**
     * The query union statements.
	 * 查询联合语句
     *
     * @var array
     */
    public $unions;

    /**
     * The maximum number of union records to return.
	 * 要返回的联合记录的最大数目
     *
     * @var int
     */
    public $unionLimit;

    /**
     * The number of union records to skip.
	 * 要跳过的联合记录的数量
     *
     * @var int
     */
    public $unionOffset;

    /**
     * The orderings for the union query.
	 * 联合查询的排序
     *
     * @var array
     */
    public $unionOrders;

    /**
     * Indicates whether row locking is being used.
	 * 指明是否正在使用行锁定
     *
     * @var string|bool
     */
    public $lock;

    /**
     * All of the available clause operators.
	 * 所有可用的子句操作符，如=,>等
     *
     * @var array
     */
    public $operators = [
        '=', '<', '>', '<=', '>=', '<>', '!=', '<=>',
        'like', 'like binary', 'not like', 'ilike',
        '&', '|', '^', '<<', '>>',
        'rlike', 'not rlike', 'regexp', 'not regexp',
        '~', '~*', '!~', '!~*', 'similar to',
        'not similar to', 'not ilike', '~~*', '!~~*',
    ];

    /**
     * Whether use write pdo for select.
	 * 是否对select使用write pdo
     *
     * @var bool
     */
    public $useWritePdo = false;

    /**
     * Create a new query builder instance.
	 * 创建新的查询建造实例
     *
     * @param  \Illuminate\Database\ConnectionInterface  $connection
     * @param  \Illuminate\Database\Query\Grammars\Grammar|null  $grammar
     * @param  \Illuminate\Database\Query\Processors\Processor|null  $processor
     * @return void
     */
    public function __construct(ConnectionInterface $connection,
                                Grammar $grammar = null,
                                Processor $processor = null)
    {
        $this->connection = $connection;
        $this->grammar = $grammar ?: $connection->getQueryGrammar();
        $this->processor = $processor ?: $connection->getPostProcessor();
    }

    /**
     * Set the columns to be selected.
	 * 设置要选择的列
     *
     * @param  array|mixed  $columns
     * @return $this
     */
    public function select($columns = ['*'])
    {
        $this->columns = [];

        $columns = is_array($columns) ? $columns : func_get_args();

        foreach ($columns as $as => $column) {
            if (is_string($as) && $this->isQueryable($column)) {
                $this->selectSub($column, $as);
            } else {
                $this->columns[] = $column;
            }
        }

        return $this;
    }

    /**
     * Add a subselect expression to the query.
	 * 添加子选择表达式至查询
     *
     * @param  \Closure|$this|string  $query
     * @param  string  $as
     * @return \Illuminate\Database\Query\Builder|static
     *
     * @throws \InvalidArgumentException
     */
    public function selectSub($query, $as)
    {
        [$query, $bindings] = $this->createSub($query);

        return $this->selectRaw(
            '('.$query.') as '.$this->grammar->wrap($as), $bindings
        );
    }

    /**
     * Add a new "raw" select expression to the query.
	 * 向查询添加一个新的"原始"选择表达式
     *
     * @param  string  $expression
     * @param  array  $bindings
     * @return \Illuminate\Database\Query\Builder|static
     */
    public function selectRaw($expression, array $bindings = [])
    {
        $this->addSelect(new Expression($expression));

        if ($bindings) {
            $this->addBinding($bindings, 'select');
        }

        return $this;
    }

    /**
     * Makes "from" fetch from a subquery.
	 * 从子查询中获取"from"
     *
     * @param  \Closure|\Illuminate\Database\Query\Builder|string  $query
     * @param  string  $as
     * @return \Illuminate\Database\Query\Builder|static
     *
     * @throws \InvalidArgumentException
     */
    public function fromSub($query, $as)
    {
        [$query, $bindings] = $this->createSub($query);

        return $this->fromRaw('('.$query.') as '.$this->grammar->wrapTable($as), $bindings);
    }

    /**
     * Add a raw from clause to the query.
	 * 添加一个原始的from子句至查询
     *
     * @param  string  $expression
     * @param  mixed  $bindings
     * @return \Illuminate\Database\Query\Builder|static
     */
    public function fromRaw($expression, $bindings = [])
    {
        $this->from = new Expression($expression);

        $this->addBinding($bindings, 'from');

        return $this;
    }

    /**
     * Creates a subquery and parse it.
	 * 创建一个子查询并解析它
     *
     * @param  \Closure|\Illuminate\Database\Query\Builder|string  $query
     * @return array
     */
    protected function createSub($query)
    {
        // If the given query is a Closure, we will execute it while passing in a new
        // query instance to the Closure. This will give the developer a chance to
        // format and work with the query before we cast it to a raw SQL string.
		// 如果给定的值是闭包，我们将在传入新的闭包时执行它。
		// 这为开发者提供一个机会在将查询转换为原始SQL字符串之前，格式化并处理它。
        if ($query instanceof Closure) {
            $callback = $query;

            $callback($query = $this->forSubQuery());
        }

        return $this->parseSub($query);
    }

    /**
     * Parse the subquery into SQL and bindings.
	 * 解析子句为SQL和绑定
     *
     * @param  mixed  $query
     * @return array
     *
     * @throws \InvalidArgumentException
     */
    protected function parseSub($query)
    {
        if ($query instanceof self || $query instanceof EloquentBuilder) {
            return [$query->toSql(), $query->getBindings()];
        } elseif (is_string($query)) {
            return [$query, []];
        } else {
            throw new InvalidArgumentException(
                'A subquery must be a query builder instance, a Closure, or a string.'
            );
        }
    }

    /**
     * Add a new select column to the query.
	 * 添加新的选择列至查询
     *
     * @param  array|mixed  $column
     * @return $this
     */
    public function addSelect($column)
    {
        $columns = is_array($column) ? $column : func_get_args();

        foreach ($columns as $as => $column) {
            if (is_string($as) && $this->isQueryable($column)) {
                if (is_null($this->columns)) {
                    $this->select($this->from.'.*');
                }

                $this->selectSub($column, $as);
            } else {
                $this->columns[] = $column;
            }
        }

        return $this;
    }

    /**
     * Force the query to only return distinct results.
	 * 强制查询只返回不同的结果
     *
     * @return $this
     */
    public function distinct()
    {
        $columns = func_get_args();

        if (count($columns) > 0) {
            $this->distinct = is_array($columns[0]) || is_bool($columns[0]) ? $columns[0] : $columns;
        } else {
            $this->distinct = true;
        }

        return $this;
    }

    /**
     * Set the table which the query is targeting.
	 * 设置查询所针对的表
     *
     * @param  \Closure|\Illuminate\Database\Query\Builder|string  $table
     * @param  string|null  $as
     * @return $this
     */
    public function from($table, $as = null)
    {
        if ($this->isQueryable($table)) {
            return $this->fromSub($table, $as);
        }

        $this->from = $as ? "{$table} as {$as}" : $table;

        return $this;
    }

    /**
     * Add a join clause to the query.
	 * 添加连接子句至查询
     *
     * @param  string  $table
     * @param  \Closure|string  $first
     * @param  string|null  $operator
     * @param  string|null  $second
     * @param  string  $type
     * @param  bool  $where
     * @return $this
     */
    public function join($table, $first, $operator = null, $second = null, $type = 'inner', $where = false)
    {
        $join = $this->newJoinClause($this, $type, $table);

        // If the first "column" of the join is really a Closure instance the developer
        // is trying to build a join with a complex "on" clause containing more than
        // one condition, so we'll add the join and call a Closure with the query.
		// 如果连接的第一个“列”确实是一个闭包实例，
		// 那么开发人员正试图构建一个包含多个条件的复杂“on”子句的连接，因此我们将添加连接并使用查询调用闭包。
        if ($first instanceof Closure) {
            $first($join);

            $this->joins[] = $join;

            $this->addBinding($join->getBindings(), 'join');
        }

        // If the column is simply a string, we can assume the join simply has a basic
        // "on" clause with a single condition. So we will just build the join with
        // this simple join clauses attached to it. There is not a join callback.
		// 如果列只是一个字符串，我们可以假设连接只是一个带有单个条件的基本"on"子句。
		// 因此，我们将只构建带有简单连接子句的连接。没有连接回调。
        else {
            $method = $where ? 'where' : 'on';

            $this->joins[] = $join->$method($first, $operator, $second);

            $this->addBinding($join->getBindings(), 'join');
        }

        return $this;
    }

    /**
     * Add a "join where" clause to the query.
	 * 添加"join where"子句至查询
     *
     * @param  string  $table
     * @param  \Closure|string  $first
     * @param  string  $operator
     * @param  string  $second
     * @param  string  $type
     * @return \Illuminate\Database\Query\Builder|static
     */
    public function joinWhere($table, $first, $operator, $second, $type = 'inner')
    {
        return $this->join($table, $first, $operator, $second, $type, true);
    }

    /**
     * Add a subquery join clause to the query.
	 * 添加子查询连接子句至查询
     *
     * @param  \Closure|\Illuminate\Database\Query\Builder|string  $query
     * @param  string  $as
     * @param  \Closure|string  $first
     * @param  string|null  $operator
     * @param  string|null  $second
     * @param  string  $type
     * @param  bool  $where
     * @return \Illuminate\Database\Query\Builder|static
     *
     * @throws \InvalidArgumentException
     */
    public function joinSub($query, $as, $first, $operator = null, $second = null, $type = 'inner', $where = false)
    {
        [$query, $bindings] = $this->createSub($query);

        $expression = '('.$query.') as '.$this->grammar->wrapTable($as);

        $this->addBinding($bindings, 'join');

        return $this->join(new Expression($expression), $first, $operator, $second, $type, $where);
    }

    /**
     * Add a left join to the query.
	 * 添加左连接至查询
     *
     * @param  string  $table
     * @param  \Closure|string  $first
     * @param  string|null  $operator
     * @param  string|null  $second
     * @return \Illuminate\Database\Query\Builder|static
     */
    public function leftJoin($table, $first, $operator = null, $second = null)
    {
        return $this->join($table, $first, $operator, $second, 'left');
    }

    /**
     * Add a "join where" clause to the query.
	 * 添加"join where"子句至查询
     *
     * @param  string  $table
     * @param  \Closure|string  $first
     * @param  string  $operator
     * @param  string  $second
     * @return \Illuminate\Database\Query\Builder|static
     */
    public function leftJoinWhere($table, $first, $operator, $second)
    {
        return $this->joinWhere($table, $first, $operator, $second, 'left');
    }

    /**
     * Add a subquery left join to the query.
	 * 添加子查询左连接至查询
     *
     * @param  \Closure|\Illuminate\Database\Query\Builder|string  $query
     * @param  string  $as
     * @param  \Closure|string  $first
     * @param  string|null  $operator
     * @param  string|null  $second
     * @return \Illuminate\Database\Query\Builder|static
     */
    public function leftJoinSub($query, $as, $first, $operator = null, $second = null)
    {
        return $this->joinSub($query, $as, $first, $operator, $second, 'left');
    }

    /**
     * Add a right join to the query.
	 * 添加右连接至查询
     *
     * @param  string  $table
     * @param  \Closure|string  $first
     * @param  string|null  $operator
     * @param  string|null  $second
     * @return \Illuminate\Database\Query\Builder|static
     */
    public function rightJoin($table, $first, $operator = null, $second = null)
    {
        return $this->join($table, $first, $operator, $second, 'right');
    }

    /**
     * Add a "right join where" clause to the query.
	 * 添加"right join where"子句至查询
     *
     * @param  string  $table
     * @param  \Closure|string  $first
     * @param  string  $operator
     * @param  string  $second
     * @return \Illuminate\Database\Query\Builder|static
     */
    public function rightJoinWhere($table, $first, $operator, $second)
    {
        return $this->joinWhere($table, $first, $operator, $second, 'right');
    }

    /**
     * Add a subquery right join to the query.
	 * 添加子查询右连接至查询
     *
     * @param  \Closure|\Illuminate\Database\Query\Builder|string  $query
     * @param  string  $as
     * @param  \Closure|string  $first
     * @param  string|null  $operator
     * @param  string|null  $second
     * @return \Illuminate\Database\Query\Builder|static
     */
    public function rightJoinSub($query, $as, $first, $operator = null, $second = null)
    {
        return $this->joinSub($query, $as, $first, $operator, $second, 'right');
    }

    /**
     * Add a "cross join" clause to the query.
	 * 添加"交叉连接"子句至查询
     *
     * @param  string  $table
     * @param  \Closure|string|null  $first
     * @param  string|null  $operator
     * @param  string|null  $second
     * @return \Illuminate\Database\Query\Builder|static
     */
    public function crossJoin($table, $first = null, $operator = null, $second = null)
    {
        if ($first) {
            return $this->join($table, $first, $operator, $second, 'cross');
        }

        $this->joins[] = $this->newJoinClause($this, 'cross', $table);

        return $this;
    }

    /**
     * Get a new join clause.
	 * 得到新的连接子句
     *
     * @param  \Illuminate\Database\Query\Builder  $parentQuery
     * @param  string  $type
     * @param  string  $table
     * @return \Illuminate\Database\Query\JoinClause
     */
    protected function newJoinClause(self $parentQuery, $type, $table)
    {
        return new JoinClause($parentQuery, $type, $table);
    }

    /**
     * Merge an array of where clauses and bindings.
	 * 合并where子句和绑定的数组
     *
     * @param  array  $wheres
     * @param  array  $bindings
     * @return void
     */
    public function mergeWheres($wheres, $bindings)
    {
        $this->wheres = array_merge($this->wheres, (array) $wheres);

        $this->bindings['where'] = array_values(
            array_merge($this->bindings['where'], (array) $bindings)
        );
    }

    /**
     * Add a basic where clause to the query.
	 * 添加一个基本的where子句至查询
     *
     * @param  \Closure|string|array  $column
     * @param  mixed  $operator
     * @param  mixed  $value
     * @param  string  $boolean
     * @return $this
     */
    public function where($column, $operator = null, $value = null, $boolean = 'and')
    {
        // If the column is an array, we will assume it is an array of key-value pairs
        // and can add them each as a where clause. We will maintain the boolean we
        // received when the method was called and pass it into the nested where.
		// 如果列是一个数组，我们将假设它是一个键值对数组，并可以将它们分别添加为where子句。
		// 我们将维护调用方法时收到的布尔值，并将其传递到嵌套的where中。
        if (is_array($column)) {
            return $this->addArrayOfWheres($column, $boolean);
        }

        // Here we will make some assumptions about the operator. If only 2 values are
        // passed to the method, we will assume that the operator is an equals sign
        // and keep going. Otherwise, we'll require the operator to be passed in.
		// 在这里，我们将对运算符做出一些假设。如果只有2个值传递给该方法，我们将假设运算符是等号并继续。
		// 否则，我们将要求传入运算符。
        [$value, $operator] = $this->prepareValueAndOperator(
            $value, $operator, func_num_args() === 2
        );

        // If the columns is actually a Closure instance, we will assume the developer
        // wants to begin a nested where statement which is wrapped in parenthesis.
        // We'll add that Closure to the query then return back out immediately.
		// 如果列实际上是一个闭包实例，我们将假设开发人员想要开始一个嵌套的where语句，该语句被包裹在括号中。
		// 我们将把闭包添加到查询中，然后立即返回。
        if ($column instanceof Closure) {
            return $this->whereNested($column, $boolean);
        }

        // If the given operator is not found in the list of valid operators we will
        // assume that the developer is just short-cutting the '=' operators and
        // we will set the operators to '=' and set the values appropriately.
		// 如果在有效运算符列表中找不到给定的运算符，我们将假设开发人员只是简化了'='运算符，
		// 我们会将运算符设置为'='并适当地设置值。
        if ($this->invalidOperator($operator)) {
            [$value, $operator] = [$operator, '='];
        }

        // If the value is a Closure, it means the developer is performing an entire
        // sub-select within the query and we will need to compile the sub-select
        // within the where clause to get the appropriate query record results.
		// 如果该值是闭包，则意味着开发人员正在查询中执行整个子选择，
		// 我们需要在where子句中编译子选择以获得适当的查询记录结果。
        if ($value instanceof Closure) {
            return $this->whereSub($column, $operator, $value, $boolean);
        }

        // If the value is "null", we will just assume the developer wants to add a
        // where null clause to the query. So, we will allow a short-cut here to
        // that method for convenience so the developer doesn't have to check.
		// 如果该值为"null"，我们将假设开发人员希望在查询中添加where null子句。
		// 因此，为了方便起见，我们将在这里为该方法提供一条捷径，这样开发人员就不必检查了。
        if (is_null($value)) {
            return $this->whereNull($column, $boolean, $operator !== '=');
        }

        $type = 'Basic';

        // If the column is making a JSON reference we'll check to see if the value
        // is a boolean. If it is, we'll add the raw boolean string as an actual
        // value to the query to ensure this is properly handled by the query.
		// 如果该列正在进行JSON引用，我们将检查该值是否为布尔值。
		// 如果是，我们将原始布尔字符串作为实际值添加到查询中，以确保查询正确处理。
        if (Str::contains($column, '->') && is_bool($value)) {
            $value = new Expression($value ? 'true' : 'false');

            if (is_string($column)) {
                $type = 'JsonBoolean';
            }
        }

        // Now that we are working with just a simple query we can put the elements
        // in our array and add the query binding to our array of bindings that
        // will be bound to each SQL statements when it is finally executed.
		// 现在我们只处理一个简单的查询，我们可以将元素放入数组中，
		// 并将查询绑定添加到绑定数组中，这些绑定将在最终执行时绑定到每个SQL语句。
        $this->wheres[] = compact(
            'type', 'column', 'operator', 'value', 'boolean'
        );

        if (! $value instanceof Expression) {
            $this->addBinding($this->flattenValue($value), 'where');
        }

        return $this;
    }

    /**
     * Add an array of where clauses to the query.
	 * 添加where子句数组至查询
     *
     * @param  array  $column
     * @param  string  $boolean
     * @param  string  $method
     * @return $this
     */
    protected function addArrayOfWheres($column, $boolean, $method = 'where')
    {
        return $this->whereNested(function ($query) use ($column, $method, $boolean) {
            foreach ($column as $key => $value) {
                if (is_numeric($key) && is_array($value)) {
                    $query->{$method}(...array_values($value));
                } else {
                    $query->$method($key, '=', $value, $boolean);
                }
            }
        }, $boolean);
    }

    /**
     * Prepare the value and operator for a where clause.
	 * 准备值和操作符为where子句
     *
     * @param  string  $value
     * @param  string  $operator
     * @param  bool  $useDefault
     * @return array
     *
     * @throws \InvalidArgumentException
     */
    public function prepareValueAndOperator($value, $operator, $useDefault = false)
    {
        if ($useDefault) {
            return [$operator, '='];
        } elseif ($this->invalidOperatorAndValue($operator, $value)) {
            throw new InvalidArgumentException('Illegal operator and value combination.');
        }

        return [$value, $operator];
    }

    /**
     * Determine if the given operator and value combination is legal.
	 * 确定给定的操作符和值组合是否合法
     *
     * Prevents using Null values with invalid operators.
     *
     * @param  string  $operator
     * @param  mixed  $value
     * @return bool
     */
    protected function invalidOperatorAndValue($operator, $value)
    {
        return is_null($value) && in_array($operator, $this->operators) &&
             ! in_array($operator, ['=', '<>', '!=']);
    }

    /**
     * Determine if the given operator is supported.
	 * 确定是否支持给定的操作符
     *
     * @param  string  $operator
     * @return bool
     */
    protected function invalidOperator($operator)
    {
        return ! in_array(strtolower($operator), $this->operators, true) &&
               ! in_array(strtolower($operator), $this->grammar->getOperators(), true);
    }

    /**
     * Add an "or where" clause to the query.
	 * 向查询添加"or where"子句
     *
     * @param  \Closure|string|array  $column
     * @param  mixed  $operator
     * @param  mixed  $value
     * @return \Illuminate\Database\Query\Builder|static
     */
    public function orWhere($column, $operator = null, $value = null)
    {
        [$value, $operator] = $this->prepareValueAndOperator(
            $value, $operator, func_num_args() === 2
        );

        return $this->where($column, $operator, $value, 'or');
    }

    /**
     * Add a "where" clause comparing two columns to the query.
	 * 添加一个"where"子句，比较查询中的两列。
     *
     * @param  string|array  $first
     * @param  string|null  $operator
     * @param  string|null  $second
     * @param  string|null  $boolean
     * @return \Illuminate\Database\Query\Builder|static
     */
    public function whereColumn($first, $operator = null, $second = null, $boolean = 'and')
    {
        // If the column is an array, we will assume it is an array of key-value pairs
        // and can add them each as a where clause. We will maintain the boolean we
        // received when the method was called and pass it into the nested where.
		// 如果列是一个数组，我们将假设它是一个键值对数组，并可以将它们分别添加为where子句。
		// 我们将维护调用方法时收到的布尔值，并将其传递到嵌套的where中。
        if (is_array($first)) {
            return $this->addArrayOfWheres($first, $boolean, 'whereColumn');
        }

        // If the given operator is not found in the list of valid operators we will
        // assume that the developer is just short-cutting the '=' operators and
        // we will set the operators to '=' and set the values appropriately.
		// 如果在有效运算符列表中找不到给定的运算符，我们将假设开发人员只是简化了'='运算符，
		// 我们会将运算符设置为'='并适当地设置值。
        if ($this->invalidOperator($operator)) {
            [$second, $operator] = [$operator, '='];
        }

        // Finally, we will add this where clause into this array of clauses that we
        // are building for the query. All of them will be compiled via a grammar
        // once the query is about to be executed and run against the database.
		// 最后，我们将把where子句添加到我们为查询构建的子句数组中。
		// 一旦查询即将执行并对数据库运行，所有这些都将通过语法进行编译
        $type = 'Column';

        $this->wheres[] = compact(
            'type', 'first', 'operator', 'second', 'boolean'
        );

        return $this;
    }

    /**
     * Add an "or where" clause comparing two columns to the query.
	 * 添加"or where"子句，将两个列与查询进行比较。
     *
     * @param  string|array  $first
     * @param  string|null  $operator
     * @param  string|null  $second
     * @return \Illuminate\Database\Query\Builder|static
     */
    public function orWhereColumn($first, $operator = null, $second = null)
    {
        return $this->whereColumn($first, $operator, $second, 'or');
    }

    /**
     * Add a raw where clause to the query.
	 * 添加一个原始的where子句至查询
     *
     * @param  string  $sql
     * @param  mixed  $bindings
     * @param  string  $boolean
     * @return $this
     */
    public function whereRaw($sql, $bindings = [], $boolean = 'and')
    {
        $this->wheres[] = ['type' => 'raw', 'sql' => $sql, 'boolean' => $boolean];

        $this->addBinding((array) $bindings, 'where');

        return $this;
    }

    /**
     * Add a raw or where clause to the query.
	 * 添加raw或where子句至查询
     *
     * @param  string  $sql
     * @param  mixed  $bindings
     * @return \Illuminate\Database\Query\Builder|static
     */
    public function orWhereRaw($sql, $bindings = [])
    {
        return $this->whereRaw($sql, $bindings, 'or');
    }

    /**
     * Add a "where in" clause to the query.
	 * 添加"where in"子句至查询
     *
     * @param  string  $column
     * @param  mixed  $values
     * @param  string  $boolean
     * @param  bool  $not
     * @return $this
     */
    public function whereIn($column, $values, $boolean = 'and', $not = false)
    {
        $type = $not ? 'NotIn' : 'In';

        // If the value is a query builder instance we will assume the developer wants to
        // look for any values that exists within this given query. So we will add the
        // query accordingly so that this query is properly executed when it is run.
		// 如果该值是一个查询构建器实例，我们将假设开发人员希望查找此给定查询中存在的任何值。
		// 因此，我们将相应地添加查询，以便在运行时正确执行此查询。
        if ($this->isQueryable($values)) {
            [$query, $bindings] = $this->createSub($values);

            $values = [new Expression($query)];

            $this->addBinding($bindings, 'where');
        }

        // Next, if the value is Arrayable we need to cast it to its raw array form so we
        // have the underlying array value instead of an Arrayable object which is not
        // able to be added as a binding, etc. We will then add to the wheres array.
		// 接下来，如果值是Arrayable，我们需要将其转换为原始数组形式，这样我们就有了基础数组值，
		// 而不是无法作为绑定添加的Arrayable对象等。然后，我们将添加到wheres数组中。
        if ($values instanceof Arrayable) {
            $values = $values->toArray();
        }

        $this->wheres[] = compact('type', 'column', 'values', 'boolean');

        // Finally we'll add a binding for each values unless that value is an expression
        // in which case we will just skip over it since it will be the query as a raw
        // string and not as a parameterized place-holder to be replaced by the PDO.
		// 最后，我们将为每个值添加一个绑定，除非该值是一个表达式，在这种情况下，
		// 我们将跳过它，因为它将作为原始字符串而不是参数化占位符被PDO替换。
        $this->addBinding($this->cleanBindings($values), 'where');

        return $this;
    }

    /**
     * Add an "or where in" clause to the query.
	 * 在查询中添加"or where in"子句
     *
     * @param  string  $column
     * @param  mixed  $values
     * @return \Illuminate\Database\Query\Builder|static
     */
    public function orWhereIn($column, $values)
    {
        return $this->whereIn($column, $values, 'or');
    }

    /**
     * Add a "where not in" clause to the query.
	 * 添加"where not in"子句至查询
     *
     * @param  string  $column
     * @param  mixed  $values
     * @param  string  $boolean
     * @return \Illuminate\Database\Query\Builder|static
     */
    public function whereNotIn($column, $values, $boolean = 'and')
    {
        return $this->whereIn($column, $values, $boolean, true);
    }

    /**
     * Add an "or where not in" clause to the query.
	 * 添加"或不在"子句至查询
     *
     * @param  string  $column
     * @param  mixed  $values
     * @return \Illuminate\Database\Query\Builder|static
     */
    public function orWhereNotIn($column, $values)
    {
        return $this->whereNotIn($column, $values, 'or');
    }

    /**
     * Add a "where in raw" clause for integer values to the query.
	 * 添加"where in raw"子句在查询中为整数值
     *
     * @param  string  $column
     * @param  \Illuminate\Contracts\Support\Arrayable|array  $values
     * @param  string  $boolean
     * @param  bool  $not
     * @return $this
     */
    public function whereIntegerInRaw($column, $values, $boolean = 'and', $not = false)
    {
        $type = $not ? 'NotInRaw' : 'InRaw';

        if ($values instanceof Arrayable) {
            $values = $values->toArray();
        }

        foreach ($values as &$value) {
            $value = (int) $value;
        }

        $this->wheres[] = compact('type', 'column', 'values', 'boolean');

        return $this;
    }

    /**
     * Add a "where not in raw" clause for integer values to the query.
	 * 添加"where not in raw"子句在查询中为整数值
     *
     * @param  string  $column
     * @param  \Illuminate\Contracts\Support\Arrayable|array  $values
     * @param  string  $boolean
     * @return $this
     */
    public function whereIntegerNotInRaw($column, $values, $boolean = 'and')
    {
        return $this->whereIntegerInRaw($column, $values, $boolean, true);
    }

    /**
     * Add a "where null" clause to the query.
	 * 添加"where null"子句在查询中
     *
     * @param  string|array  $columns
     * @param  string  $boolean
     * @param  bool  $not
     * @return $this
     */
    public function whereNull($columns, $boolean = 'and', $not = false)
    {
        $type = $not ? 'NotNull' : 'Null';

        foreach (Arr::wrap($columns) as $column) {
            $this->wheres[] = compact('type', 'column', 'boolean');
        }

        return $this;
    }

    /**
     * Add an "or where null" clause to the query.
	 * 向查询添加"or where null"子句
     *
     * @param  string  $column
     * @return \Illuminate\Database\Query\Builder|static
     */
    public function orWhereNull($column)
    {
        return $this->whereNull($column, 'or');
    }

    /**
     * Add a "where not null" clause to the query.
	 * 添加"where not null"子句在查询中
     *
     * @param  string|array  $columns
     * @param  string  $boolean
     * @return \Illuminate\Database\Query\Builder|static
     */
    public function whereNotNull($columns, $boolean = 'and')
    {
        return $this->whereNull($columns, $boolean, true);
    }

    /**
     * Add a where between statement to the query.
	 * 添加where between语句至查询
     *
     * @param  string  $column
     * @param  array  $values
     * @param  string  $boolean
     * @param  bool  $not
     * @return $this
     */
    public function whereBetween($column, array $values, $boolean = 'and', $not = false)
    {
        $type = 'between';

        $this->wheres[] = compact('type', 'column', 'values', 'boolean', 'not');

        $this->addBinding(array_slice($this->cleanBindings(Arr::flatten($values)), 0, 2), 'where');

        return $this;
    }

    /**
     * Add an or where between statement to the query.
	 * 添加or where between语句至查询中
     *
     * @param  string  $column
     * @param  array  $values
     * @return \Illuminate\Database\Query\Builder|static
     */
    public function orWhereBetween($column, array $values)
    {
        return $this->whereBetween($column, $values, 'or');
    }

    /**
     * Add a where not between statement to the query.
	 * 添加where not between语句至查询中
     *
     * @param  string  $column
     * @param  array  $values
     * @param  string  $boolean
     * @return \Illuminate\Database\Query\Builder|static
     */
    public function whereNotBetween($column, array $values, $boolean = 'and')
    {
        return $this->whereBetween($column, $values, $boolean, true);
    }

    /**
     * Add an or where not between statement to the query.
	 * 在查询中添加or where not between语句
     *
     * @param  string  $column
     * @param  array  $values
     * @return \Illuminate\Database\Query\Builder|static
     */
    public function orWhereNotBetween($column, array $values)
    {
        return $this->whereNotBetween($column, $values, 'or');
    }

    /**
     * Add an "or where not null" clause to the query.
	 * 添加一个"or where not null"子句至查询中
     *
     * @param  string  $column
     * @return \Illuminate\Database\Query\Builder|static
     */
    public function orWhereNotNull($column)
    {
        return $this->whereNotNull($column, 'or');
    }

    /**
     * Add a "where date" statement to the query.
	 * 添加一个"where date"子句至查询中
     *
     * @param  string  $column
     * @param  string  $operator
     * @param  \DateTimeInterface|string|null  $value
     * @param  string  $boolean
     * @return \Illuminate\Database\Query\Builder|static
     */
    public function whereDate($column, $operator, $value = null, $boolean = 'and')
    {
        [$value, $operator] = $this->prepareValueAndOperator(
            $value, $operator, func_num_args() === 2
        );

        $value = $this->flattenValue($value);

        if ($value instanceof DateTimeInterface) {
            $value = $value->format('Y-m-d');
        }

        return $this->addDateBasedWhere('Date', $column, $operator, $value, $boolean);
    }

    /**
     * Add an "or where date" statement to the query.
	 * 添加"or where date”语句至查询
     *
     * @param  string  $column
     * @param  string  $operator
     * @param  \DateTimeInterface|string|null  $value
     * @return \Illuminate\Database\Query\Builder|static
     */
    public function orWhereDate($column, $operator, $value = null)
    {
        [$value, $operator] = $this->prepareValueAndOperator(
            $value, $operator, func_num_args() === 2
        );

        return $this->whereDate($column, $operator, $value, 'or');
    }

    /**
     * Add a "where time" statement to the query.
	 * 添加"where time"语句至查询
     *
     * @param  string  $column
     * @param  string  $operator
     * @param  \DateTimeInterface|string|null  $value
     * @param  string  $boolean
     * @return \Illuminate\Database\Query\Builder|static
     */
    public function whereTime($column, $operator, $value = null, $boolean = 'and')
    {
        [$value, $operator] = $this->prepareValueAndOperator(
            $value, $operator, func_num_args() === 2
        );

        $value = $this->flattenValue($value);

        if ($value instanceof DateTimeInterface) {
            $value = $value->format('H:i:s');
        }

        return $this->addDateBasedWhere('Time', $column, $operator, $value, $boolean);
    }

    /**
     * Add an "or where time" statement to the query.
	 * 添加"or where time"语句至查询中
     *
     * @param  string  $column
     * @param  string  $operator
     * @param  \DateTimeInterface|string|null  $value
     * @return \Illuminate\Database\Query\Builder|static
     */
    public function orWhereTime($column, $operator, $value = null)
    {
        [$value, $operator] = $this->prepareValueAndOperator(
            $value, $operator, func_num_args() === 2
        );

        return $this->whereTime($column, $operator, $value, 'or');
    }

    /**
     * Add a "where day" statement to the query.
	 * 添加"where day"语句至查询中
     *
     * @param  string  $column
     * @param  string  $operator
     * @param  \DateTimeInterface|string|null  $value
     * @param  string  $boolean
     * @return \Illuminate\Database\Query\Builder|static
     */
    public function whereDay($column, $operator, $value = null, $boolean = 'and')
    {
        [$value, $operator] = $this->prepareValueAndOperator(
            $value, $operator, func_num_args() === 2
        );

        $value = $this->flattenValue($value);

        if ($value instanceof DateTimeInterface) {
            $value = $value->format('d');
        }

        if (! $value instanceof Expression) {
            $value = str_pad($value, 2, '0', STR_PAD_LEFT);
        }

        return $this->addDateBasedWhere('Day', $column, $operator, $value, $boolean);
    }

    /**
     * Add an "or where day" statement to the query.
	 * 添加"or where day"语句至查询中
     *
     * @param  string  $column
     * @param  string  $operator
     * @param  \DateTimeInterface|string|null  $value
     * @return \Illuminate\Database\Query\Builder|static
     */
    public function orWhereDay($column, $operator, $value = null)
    {
        [$value, $operator] = $this->prepareValueAndOperator(
            $value, $operator, func_num_args() === 2
        );

        return $this->whereDay($column, $operator, $value, 'or');
    }

    /**
     * Add a "where month" statement to the query.
	 * 添加"where month"语句至查询中
     *
     * @param  string  $column
     * @param  string  $operator
     * @param  \DateTimeInterface|string|null  $value
     * @param  string  $boolean
     * @return \Illuminate\Database\Query\Builder|static
     */
    public function whereMonth($column, $operator, $value = null, $boolean = 'and')
    {
        [$value, $operator] = $this->prepareValueAndOperator(
            $value, $operator, func_num_args() === 2
        );

        $value = $this->flattenValue($value);

        if ($value instanceof DateTimeInterface) {
            $value = $value->format('m');
        }

        if (! $value instanceof Expression) {
            $value = str_pad($value, 2, '0', STR_PAD_LEFT);
        }

        return $this->addDateBasedWhere('Month', $column, $operator, $value, $boolean);
    }

    /**
     * Add an "or where month" statement to the query.
	 * 添加"or where month"语句向查询中
     *
     * @param  string  $column
     * @param  string  $operator
     * @param  \DateTimeInterface|string|null  $value
     * @return \Illuminate\Database\Query\Builder|static
     */
    public function orWhereMonth($column, $operator, $value = null)
    {
        [$value, $operator] = $this->prepareValueAndOperator(
            $value, $operator, func_num_args() === 2
        );

        return $this->whereMonth($column, $operator, $value, 'or');
    }

    /**
     * Add a "where year" statement to the query.
	 * 添加"where year"语句向查询中
     *
     * @param  string  $column
     * @param  string  $operator
     * @param  \DateTimeInterface|string|int|null  $value
     * @param  string  $boolean
     * @return \Illuminate\Database\Query\Builder|static
     */
    public function whereYear($column, $operator, $value = null, $boolean = 'and')
    {
        [$value, $operator] = $this->prepareValueAndOperator(
            $value, $operator, func_num_args() === 2
        );

        $value = $this->flattenValue($value);

        if ($value instanceof DateTimeInterface) {
            $value = $value->format('Y');
        }

        return $this->addDateBasedWhere('Year', $column, $operator, $value, $boolean);
    }

    /**
     * Add an "or where year" statement to the query.
	 * 添加"or where year"语句至查询中
     *
     * @param  string  $column
     * @param  string  $operator
     * @param  \DateTimeInterface|string|int|null  $value
     * @return \Illuminate\Database\Query\Builder|static
     */
    public function orWhereYear($column, $operator, $value = null)
    {
        [$value, $operator] = $this->prepareValueAndOperator(
            $value, $operator, func_num_args() === 2
        );

        return $this->whereYear($column, $operator, $value, 'or');
    }

    /**
     * Add a date based (year, month, day, time) statement to the query.
	 * 添加基于日期(年、月、日、时间)的语句向查询中
     *
     * @param  string  $type
     * @param  string  $column
     * @param  string  $operator
     * @param  mixed  $value
     * @param  string  $boolean
     * @return $this
     */
    protected function addDateBasedWhere($type, $column, $operator, $value, $boolean = 'and')
    {
        $this->wheres[] = compact('column', 'type', 'boolean', 'operator', 'value');

        if (! $value instanceof Expression) {
            $this->addBinding($value, 'where');
        }

        return $this;
    }

    /**
     * Add a nested where statement to the query.
	 * 添加嵌套的where语句向查询
     *
     * @param  \Closure  $callback
     * @param  string  $boolean
     * @return \Illuminate\Database\Query\Builder|static
     */
    public function whereNested(Closure $callback, $boolean = 'and')
    {
        call_user_func($callback, $query = $this->forNestedWhere());

        return $this->addNestedWhereQuery($query, $boolean);
    }

    /**
     * Create a new query instance for nested where condition.
	 * 创建新的查询实例为嵌套where条件
     *
     * @return \Illuminate\Database\Query\Builder
     */
    public function forNestedWhere()
    {
        return $this->newQuery()->from($this->from);
    }

    /**
     * Add another query builder as a nested where to the query builder.
	 * 添加另一个查询构建器为查询构建器的嵌套位置
     *
     * @param  \Illuminate\Database\Query\Builder|static  $query
     * @param  string  $boolean
     * @return $this
     */
    public function addNestedWhereQuery($query, $boolean = 'and')
    {
        if (count($query->wheres)) {
            $type = 'Nested';

            $this->wheres[] = compact('type', 'query', 'boolean');

            $this->addBinding($query->getRawBindings()['where'], 'where');
        }

        return $this;
    }

    /**
     * Add a full sub-select to the query.
	 * 添加一个完整的子选择至查询中
     *
     * @param  string  $column
     * @param  string  $operator
     * @param  \Closure  $callback
     * @param  string  $boolean
     * @return $this
     */
    protected function whereSub($column, $operator, Closure $callback, $boolean)
    {
        $type = 'Sub';

        // Once we have the query instance we can simply execute it so it can add all
        // of the sub-select's conditions to itself, and then we can cache it off
        // in the array of where clauses for the "main" parent query instance.
		// 一旦我们有了查询实例，我们就可以简单地执行它，这样它就可以将所有子选择的条件添加到自己身上，
		// 然后我们可以将其缓存在“主”父查询实例的where子句数组之外。
        call_user_func($callback, $query = $this->forSubQuery());

        $this->wheres[] = compact(
            'type', 'column', 'operator', 'query', 'boolean'
        );

        $this->addBinding($query->getBindings(), 'where');

        return $this;
    }

    /**
     * Add an exists clause to the query.
	 * 向查询添加exists子句
     *
     * @param  \Closure  $callback
     * @param  string  $boolean
     * @param  bool  $not
     * @return $this
     */
    public function whereExists(Closure $callback, $boolean = 'and', $not = false)
    {
        $query = $this->forSubQuery();

        // Similar to the sub-select clause, we will create a new query instance so
        // the developer may cleanly specify the entire exists query and we will
        // compile the whole thing in the grammar and insert it into the SQL.
		// 与sub-select子句类似，我们将创建一个新的查询实例，
		// 以便开发人员可以清楚地指定整个exists查询，我们将用语法编译整个查询并将其插入SQL。
        call_user_func($callback, $query);

        return $this->addWhereExistsQuery($query, $boolean, $not);
    }

    /**
     * Add an or exists clause to the query.
	 * 添加or exists子句至查询
     *
     * @param  \Closure  $callback
     * @param  bool  $not
     * @return \Illuminate\Database\Query\Builder|static
     */
    public function orWhereExists(Closure $callback, $not = false)
    {
        return $this->whereExists($callback, 'or', $not);
    }

    /**
     * Add a where not exists clause to the query.
	 * 添加where not exists子句至查询
     *
     * @param  \Closure  $callback
     * @param  string  $boolean
     * @return \Illuminate\Database\Query\Builder|static
     */
    public function whereNotExists(Closure $callback, $boolean = 'and')
    {
        return $this->whereExists($callback, $boolean, true);
    }

    /**
     * Add a where not exists clause to the query.
	 * 添加where not exists子句至查询中
     *
     * @param  \Closure  $callback
     * @return \Illuminate\Database\Query\Builder|static
     */
    public function orWhereNotExists(Closure $callback)
    {
        return $this->orWhereExists($callback, true);
    }

    /**
     * Add an exists clause to the query.
	 * 添加exists子句至查询
     *
     * @param  \Illuminate\Database\Query\Builder  $query
     * @param  string  $boolean
     * @param  bool  $not
     * @return $this
     */
    public function addWhereExistsQuery(self $query, $boolean = 'and', $not = false)
    {
        $type = $not ? 'NotExists' : 'Exists';

        $this->wheres[] = compact('type', 'query', 'boolean');

        $this->addBinding($query->getBindings(), 'where');

        return $this;
    }

    /**
     * Adds a where condition using row values.
	 * 添加where条件使用行值
     *
     * @param  array  $columns
     * @param  string  $operator
     * @param  array  $values
     * @param  string  $boolean
     * @return $this
     *
     * @throws \InvalidArgumentException
     */
    public function whereRowValues($columns, $operator, $values, $boolean = 'and')
    {
        if (count($columns) !== count($values)) {
            throw new InvalidArgumentException('The number of columns must match the number of values');
        }

        $type = 'RowValues';

        $this->wheres[] = compact('type', 'columns', 'operator', 'values', 'boolean');

        $this->addBinding($this->cleanBindings($values));

        return $this;
    }

    /**
     * Adds a or where condition using row values.
	 * 添加或where条件使用行值
     *
     * @param  array  $columns
     * @param  string  $operator
     * @param  array  $values
     * @return $this
     */
    public function orWhereRowValues($columns, $operator, $values)
    {
        return $this->whereRowValues($columns, $operator, $values, 'or');
    }

    /**
     * Add a "where JSON contains" clause to the query.
	 * 添加"where JSON contains"子句至查询中
     *
     * @param  string  $column
     * @param  mixed  $value
     * @param  string  $boolean
     * @param  bool  $not
     * @return $this
     */
    public function whereJsonContains($column, $value, $boolean = 'and', $not = false)
    {
        $type = 'JsonContains';

        $this->wheres[] = compact('type', 'column', 'value', 'boolean', 'not');

        if (! $value instanceof Expression) {
            $this->addBinding($this->grammar->prepareBindingForJsonContains($value));
        }

        return $this;
    }

    /**
     * Add a "or where JSON contains" clause to the query.
	 * 添加"or where JSON contains"子句至查询中
     *
     * @param  string  $column
     * @param  mixed  $value
     * @return $this
     */
    public function orWhereJsonContains($column, $value)
    {
        return $this->whereJsonContains($column, $value, 'or');
    }

    /**
     * Add a "where JSON not contains" clause to the query.
     *
     * @param  string  $column
     * @param  mixed  $value
     * @param  string  $boolean
     * @return $this
     */
    public function whereJsonDoesntContain($column, $value, $boolean = 'and')
    {
        return $this->whereJsonContains($column, $value, $boolean, true);
    }

    /**
     * Add a "or where JSON not contains" clause to the query.
     *
     * @param  string  $column
     * @param  mixed  $value
     * @return $this
     */
    public function orWhereJsonDoesntContain($column, $value)
    {
        return $this->whereJsonDoesntContain($column, $value, 'or');
    }

    /**
     * Add a "where JSON length" clause to the query.
     *
     * @param  string  $column
     * @param  mixed  $operator
     * @param  mixed  $value
     * @param  string  $boolean
     * @return $this
     */
    public function whereJsonLength($column, $operator, $value = null, $boolean = 'and')
    {
        $type = 'JsonLength';

        [$value, $operator] = $this->prepareValueAndOperator(
            $value, $operator, func_num_args() === 2
        );

        $this->wheres[] = compact('type', 'column', 'operator', 'value', 'boolean');

        if (! $value instanceof Expression) {
            $this->addBinding((int) $this->flattenValue($value));
        }

        return $this;
    }

    /**
     * Add a "or where JSON length" clause to the query.
     *
     * @param  string  $column
     * @param  mixed  $operator
     * @param  mixed  $value
     * @return $this
     */
    public function orWhereJsonLength($column, $operator, $value = null)
    {
        [$value, $operator] = $this->prepareValueAndOperator(
            $value, $operator, func_num_args() === 2
        );

        return $this->whereJsonLength($column, $operator, $value, 'or');
    }

    /**
     * Handles dynamic "where" clauses to the query.
     *
     * @param  string  $method
     * @param  array  $parameters
     * @return $this
     */
    public function dynamicWhere($method, $parameters)
    {
        $finder = substr($method, 5);

        $segments = preg_split(
            '/(And|Or)(?=[A-Z])/', $finder, -1, PREG_SPLIT_DELIM_CAPTURE
        );

        // The connector variable will determine which connector will be used for the
        // query condition. We will change it as we come across new boolean values
        // in the dynamic method strings, which could contain a number of these.
        $connector = 'and';

        $index = 0;

        foreach ($segments as $segment) {
            // If the segment is not a boolean connector, we can assume it is a column's name
            // and we will add it to the query as a new constraint as a where clause, then
            // we can keep iterating through the dynamic method string's segments again.
            if ($segment !== 'And' && $segment !== 'Or') {
                $this->addDynamic($segment, $connector, $parameters, $index);

                $index++;
            }

            // Otherwise, we will store the connector so we know how the next where clause we
            // find in the query should be connected to the previous ones, meaning we will
            // have the proper boolean connector to connect the next where clause found.
            else {
                $connector = $segment;
            }
        }

        return $this;
    }

    /**
     * Add a single dynamic where clause statement to the query.
     *
     * @param  string  $segment
     * @param  string  $connector
     * @param  array  $parameters
     * @param  int  $index
     * @return void
     */
    protected function addDynamic($segment, $connector, $parameters, $index)
    {
        // Once we have parsed out the columns and formatted the boolean operators we
        // are ready to add it to this query as a where clause just like any other
        // clause on the query. Then we'll increment the parameter index values.
        $bool = strtolower($connector);

        $this->where(Str::snake($segment), '=', $parameters[$index], $bool);
    }

    /**
     * Add a "group by" clause to the query.
     *
     * @param  array|string  ...$groups
     * @return $this
     */
    public function groupBy(...$groups)
    {
        foreach ($groups as $group) {
            $this->groups = array_merge(
                (array) $this->groups,
                Arr::wrap($group)
            );
        }

        return $this;
    }

    /**
     * Add a raw groupBy clause to the query.
     *
     * @param  string  $sql
     * @param  array  $bindings
     * @return $this
     */
    public function groupByRaw($sql, array $bindings = [])
    {
        $this->groups[] = new Expression($sql);

        $this->addBinding($bindings, 'groupBy');

        return $this;
    }

    /**
     * Add a "having" clause to the query.
     *
     * @param  string  $column
     * @param  string|null  $operator
     * @param  string|null  $value
     * @param  string  $boolean
     * @return $this
     */
    public function having($column, $operator = null, $value = null, $boolean = 'and')
    {
        $type = 'Basic';

        // Here we will make some assumptions about the operator. If only 2 values are
        // passed to the method, we will assume that the operator is an equals sign
        // and keep going. Otherwise, we'll require the operator to be passed in.
        [$value, $operator] = $this->prepareValueAndOperator(
            $value, $operator, func_num_args() === 2
        );

        // If the given operator is not found in the list of valid operators we will
        // assume that the developer is just short-cutting the '=' operators and
        // we will set the operators to '=' and set the values appropriately.
		// 如果在有效运算符列表中找不到给定的运算符，我们将假设开发人员只是简化了'='运算符，
		// 我们会将运算符设置为'='并适当地设置值。
        if ($this->invalidOperator($operator)) {
            [$value, $operator] = [$operator, '='];
        }

        $this->havings[] = compact('type', 'column', 'operator', 'value', 'boolean');

        if (! $value instanceof Expression) {
            $this->addBinding($this->flattenValue($value), 'having');
        }

        return $this;
    }

    /**
     * Add a "or having" clause to the query.
	 * 添加"or having"子句至查询中
     *
     * @param  string  $column
     * @param  string|null  $operator
     * @param  string|null  $value
     * @return \Illuminate\Database\Query\Builder|static
     */
    public function orHaving($column, $operator = null, $value = null)
    {
        [$value, $operator] = $this->prepareValueAndOperator(
            $value, $operator, func_num_args() === 2
        );

        return $this->having($column, $operator, $value, 'or');
    }

    /**
     * Add a "having between " clause to the query.
	 * 添加"having between "子句至查询中
     *
     * @param  string  $column
     * @param  array  $values
     * @param  string  $boolean
     * @param  bool  $not
     * @return \Illuminate\Database\Query\Builder|static
     */
    public function havingBetween($column, array $values, $boolean = 'and', $not = false)
    {
        $type = 'between';

        $this->havings[] = compact('type', 'column', 'values', 'boolean', 'not');

        $this->addBinding(array_slice($this->cleanBindings(Arr::flatten($values)), 0, 2), 'having');

        return $this;
    }

    /**
     * Add a raw having clause to the query.
	 * 添加原始having子句至查询中
     *
     * @param  string  $sql
     * @param  array  $bindings
     * @param  string  $boolean
     * @return $this
     */
    public function havingRaw($sql, array $bindings = [], $boolean = 'and')
    {
        $type = 'Raw';

        $this->havings[] = compact('type', 'sql', 'boolean');

        $this->addBinding($bindings, 'having');

        return $this;
    }

    /**
     * Add a raw or having clause to the query.
	 * 添加原始having子句至查询中
     *
     * @param  string  $sql
     * @param  array  $bindings
     * @return \Illuminate\Database\Query\Builder|static
     */
    public function orHavingRaw($sql, array $bindings = [])
    {
        return $this->havingRaw($sql, $bindings, 'or');
    }

    /**
     * Add an "order by" clause to the query.
	 * 添加"order by"子句至查询中
     *
     * @param  \Closure|\Illuminate\Database\Query\Builder|\Illuminate\Database\Query\Expression|string  $column
     * @param  string  $direction
     * @return $this
     *
     * @throws \InvalidArgumentException
     */
    public function orderBy($column, $direction = 'asc')
    {
        if ($this->isQueryable($column)) {
            [$query, $bindings] = $this->createSub($column);

            $column = new Expression('('.$query.')');

            $this->addBinding($bindings, $this->unions ? 'unionOrder' : 'order');
        }

        $direction = strtolower($direction);

        if (! in_array($direction, ['asc', 'desc'], true)) {
            throw new InvalidArgumentException('Order direction must be "asc" or "desc".');
        }

        $this->{$this->unions ? 'unionOrders' : 'orders'}[] = [
            'column' => $column,
            'direction' => $direction,
        ];

        return $this;
    }

    /**
     * Add a descending "order by" clause to the query.
	 * 添加降序"order by"子句至查询中
     *
     * @param  string  $column
     * @return $this
     */
    public function orderByDesc($column)
    {
        return $this->orderBy($column, 'desc');
    }

    /**
     * Add an "order by" clause for a timestamp to the query.
	 * 添加"order by"子句至查询中
     *
     * @param  string  $column
     * @return \Illuminate\Database\Query\Builder|static
     */
    public function latest($column = 'created_at')
    {
        return $this->orderBy($column, 'desc');
    }

    /**
     * Add an "order by" clause for a timestamp to the query.
	 * 添加"order by"子句至查询中
     *
     * @param  string  $column
     * @return \Illuminate\Database\Query\Builder|static
     */
    public function oldest($column = 'created_at')
    {
        return $this->orderBy($column, 'asc');
    }

    /**
     * Put the query's results in random order.
	 * 将查询的结果按随机顺序排列
     *
     * @param  string  $seed
     * @return $this
     */
    public function inRandomOrder($seed = '')
    {
        return $this->orderByRaw($this->grammar->compileRandom($seed));
    }

    /**
     * Add a raw "order by" clause to the query.
	 * 添加一个原始的"order by"子句至查询中
     *
     * @param  string  $sql
     * @param  array  $bindings
     * @return $this
     */
    public function orderByRaw($sql, $bindings = [])
    {
        $type = 'Raw';

        $this->{$this->unions ? 'unionOrders' : 'orders'}[] = compact('type', 'sql');

        $this->addBinding($bindings, $this->unions ? 'unionOrder' : 'order');

        return $this;
    }

    /**
     * Alias to set the "offset" value of the query.
	 * 设置查询的"偏移"值的别名
     *
     * @param  int  $value
     * @return \Illuminate\Database\Query\Builder|static
     */
    public function skip($value)
    {
        return $this->offset($value);
    }

    /**
     * Set the "offset" value of the query.
	 * 设置查询的"offset"值
     *
     * @param  int  $value
     * @return $this
     */
    public function offset($value)
    {
        $property = $this->unions ? 'unionOffset' : 'offset';

        $this->$property = max(0, (int) $value);

        return $this;
    }

    /**
     * Alias to set the "limit" value of the query.
	 * 设置查询的"限制"值的别名
     *
     * @param  int  $value
     * @return \Illuminate\Database\Query\Builder|static
     */
    public function take($value)
    {
        return $this->limit($value);
    }

    /**
     * Set the "limit" value of the query.
	 * 设置查询的"limit"值
     *
     * @param  int  $value
     * @return $this
     */
    public function limit($value)
    {
        $property = $this->unions ? 'unionLimit' : 'limit';

        if ($value >= 0) {
            $this->$property = ! is_null($value) ? (int) $value : null;
        }

        return $this;
    }

    /**
     * Set the limit and offset for a given page.
	 * 设置给定页面的限制和偏移量
     *
     * @param  int  $page
     * @param  int  $perPage
     * @return \Illuminate\Database\Query\Builder|static
     */
    public function forPage($page, $perPage = 15)
    {
        return $this->offset(($page - 1) * $perPage)->limit($perPage);
    }

    /**
     * Constrain the query to the previous "page" of results before a given ID.
	 * 将查询约束到给定ID之前的前一个结果"页"
     *
     * @param  int  $perPage
     * @param  int|null  $lastId
     * @param  string  $column
     * @return \Illuminate\Database\Query\Builder|static
     */
    public function forPageBeforeId($perPage = 15, $lastId = 0, $column = 'id')
    {
        $this->orders = $this->removeExistingOrdersFor($column);

        if (! is_null($lastId)) {
            $this->where($column, '<', $lastId);
        }

        return $this->orderBy($column, 'desc')
                    ->limit($perPage);
    }

    /**
     * Constrain the query to the next "page" of results after a given ID.
	 * 将查询约束到给定ID之后的下一个结果"页面"
     *
     * @param  int  $perPage
     * @param  int|null  $lastId
     * @param  string  $column
     * @return \Illuminate\Database\Query\Builder|static
     */
    public function forPageAfterId($perPage = 15, $lastId = 0, $column = 'id')
    {
        $this->orders = $this->removeExistingOrdersFor($column);

        if (! is_null($lastId)) {
            $this->where($column, '>', $lastId);
        }

        return $this->orderBy($column, 'asc')
                    ->limit($perPage);
    }

    /**
     * Get an array with all orders with a given column removed.
	 * 得到一个数组，其中包含删除给定列的所有订单。
     *
     * @param  string  $column
     * @return array
     */
    protected function removeExistingOrdersFor($column)
    {
        return Collection::make($this->orders)
                    ->reject(function ($order) use ($column) {
                        return isset($order['column'])
                               ? $order['column'] === $column : false;
                    })->values()->all();
    }

    /**
     * Add a union statement to the query.
	 * 添加一个联合语句至查询中
     *
     * @param  \Illuminate\Database\Query\Builder|\Closure  $query
     * @param  bool  $all
     * @return \Illuminate\Database\Query\Builder|static
     */
    public function union($query, $all = false)
    {
        if ($query instanceof Closure) {
            call_user_func($query, $query = $this->newQuery());
        }

        $this->unions[] = compact('query', 'all');

        $this->addBinding($query->getBindings(), 'union');

        return $this;
    }

    /**
     * Add a union all statement to the query.
	 * 添加一个联合all语句至查询中
     *
     * @param  \Illuminate\Database\Query\Builder|\Closure  $query
     * @return \Illuminate\Database\Query\Builder|static
     */
    public function unionAll($query)
    {
        return $this->union($query, true);
    }

    /**
     * Lock the selected rows in the table.
	 * 锁定表中选定的行
     *
     * @param  string|bool  $value
     * @return $this
     */
    public function lock($value = true)
    {
        $this->lock = $value;

        if (! is_null($this->lock)) {
            $this->useWritePdo();
        }

        return $this;
    }

    /**
     * Lock the selected rows in the table for updating.
	 * 锁定表中所选的行以进行更新
     *
     * @return \Illuminate\Database\Query\Builder
     */
    public function lockForUpdate()
    {
        return $this->lock(true);
    }

    /**
     * Share lock the selected rows in the table.
	 * 共享锁表中选定的行
     *
     * @return \Illuminate\Database\Query\Builder
     */
    public function sharedLock()
    {
        return $this->lock(false);
    }

    /**
     * Get the SQL representation of the query.
	 * 得到查询的SQL表示形式
     *
     * @return string
     */
    public function toSql()
    {
        return $this->grammar->compileSelect($this);
    }

    /**
     * Execute a query for a single record by ID.
	 * 执行查询按ID对单个记录
     *
     * @param  int|string  $id
     * @param  array  $columns
     * @return mixed|static
     */
    public function find($id, $columns = ['*'])
    {
        return $this->where('id', '=', $id)->first($columns);
    }

    /**
     * Get a single column's value from the first result of a query.
	 * 得到单个列的值从查询的第一个结果中
     *
     * @param  string  $column
     * @return mixed
     */
    public function value($column)
    {
        $result = (array) $this->first([$column]);

        return count($result) > 0 ? reset($result) : null;
    }

    /**
     * Execute the query as a "select" statement.
	 * 执行查询以select语句的形式
     *
     * @param  array|string  $columns
     * @return \Illuminate\Support\Collection
     */
    public function get($columns = ['*'])
    {
        return collect($this->onceWithColumns(Arr::wrap($columns), function () {
            return $this->processor->processSelect($this, $this->runSelect());
        }));
    }

    /**
     * Run the query as a "select" statement against the connection.
	 * 运行查询以select语句的形式对连接
     *
     * @return array
     */
    protected function runSelect()
    {
		//实际执行Connection 353行： public function select
        return $this->connection->select(
            $this->toSql(), $this->getBindings(), ! $this->useWritePdo
        );
    }

    /**
     * Paginate the given query into a simple paginator.
	 * 分页给定查询到一个简单的分页器中
     *
     * @param  int  $perPage
     * @param  array  $columns
     * @param  string  $pageName
     * @param  int|null  $page
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    public function paginate($perPage = 15, $columns = ['*'], $pageName = 'page', $page = null)
    {
        $page = $page ?: Paginator::resolveCurrentPage($pageName);

        $total = $this->getCountForPagination();

        $results = $total ? $this->forPage($page, $perPage)->get($columns) : collect();

        return $this->paginator($results, $total, $perPage, $page, [
            'path' => Paginator::resolveCurrentPath(),
            'pageName' => $pageName,
        ]);
    }

    /**
     * Get a paginator only supporting simple next and previous links.
	 * 得到只支持简单的下一个和上一个链接的分页器
     *
     * This is more efficient on larger data-sets, etc.
     *
     * @param  int  $perPage
     * @param  array  $columns
     * @param  string  $pageName
     * @param  int|null  $page
     * @return \Illuminate\Contracts\Pagination\Paginator
     */
    public function simplePaginate($perPage = 15, $columns = ['*'], $pageName = 'page', $page = null)
    {
        $page = $page ?: Paginator::resolveCurrentPage($pageName);

        $this->offset(($page - 1) * $perPage)->limit($perPage + 1);

        return $this->simplePaginator($this->get($columns), $perPage, $page, [
            'path' => Paginator::resolveCurrentPath(),
            'pageName' => $pageName,
        ]);
    }

    /**
     * Get the count of the total records for the paginator.
	 * 得到分页器的总记录计数
     *
     * @param  array  $columns
     * @return int
     */
    public function getCountForPagination($columns = ['*'])
    {
        $results = $this->runPaginationCountQuery($columns);

        // Once we have run the pagination count query, we will get the resulting count and
        // take into account what type of query it was. When there is a group by we will
        // just return the count of the entire results set since that will be correct.
		// 一旦我们运行了分页计数查询，我们将得到结果计数，并考虑它是什么类型的查询。
		// 当有一个分组时，我们只会返回整个结果集的计数，因为这是正确的。
        if (isset($this->groups)) {
            return count($results);
        } elseif (! isset($results[0])) {
            return 0;
        } elseif (is_object($results[0])) {
            return (int) $results[0]->aggregate;
        }

        return (int) array_change_key_case((array) $results[0])['aggregate'];
    }

    /**
     * Run a pagination count query.
	 * 运行分页计数查询
     *
     * @param  array  $columns
     * @return array
     */
    protected function runPaginationCountQuery($columns = ['*'])
    {
        $without = $this->unions ? ['orders', 'limit', 'offset'] : ['columns', 'orders', 'limit', 'offset'];

        return $this->cloneWithout($without)
                    ->cloneWithoutBindings($this->unions ? ['order'] : ['select', 'order'])
                    ->setAggregate('count', $this->withoutSelectAliases($columns))
                    ->get()->all();
    }

    /**
     * Remove the column aliases since they will break count queries.
	 * 删除列别名，因为它们会中断计数查询
     *
     * @param  array  $columns
     * @return array
     */
    protected function withoutSelectAliases(array $columns)
    {
        return array_map(function ($column) {
            return is_string($column) && ($aliasPosition = stripos($column, ' as ')) !== false
                    ? substr($column, 0, $aliasPosition) : $column;
        }, $columns);
    }

    /**
     * Get a lazy collection for the given query.
	 * 得到给定查询的惰性集合
     *
     * @return \Illuminate\Support\LazyCollection
     */
    public function cursor()
    {
        if (is_null($this->columns)) {
            $this->columns = ['*'];
        }

        return new LazyCollection(function () {
            yield from $this->connection->cursor(
                $this->toSql(), $this->getBindings(), ! $this->useWritePdo
            );
        });
    }

    /**
     * Throw an exception if the query doesn't have an orderBy clause.
	 * 如果查询没有orderBy子句，则抛出异常。
     *
     * @return void
     *
     * @throws \RuntimeException
     */
    protected function enforceOrderBy()
    {
        if (empty($this->orders) && empty($this->unionOrders)) {
            throw new RuntimeException('You must specify an orderBy clause when using this function.');
        }
    }

    /**
     * Get an array with the values of a given column.
	 * 得到包含给定列值的数组
     *
     * @param  string  $column
     * @param  string|null  $key
     * @return \Illuminate\Support\Collection
     */
    public function pluck($column, $key = null)
    {
        // First, we will need to select the results of the query accounting for the
        // given columns / key. Once we have the results, we will be able to take
        // the results and get the exact data that was requested for the query.
		// 首先，我们需要为给定的列/键选择查询结果。一旦我们有了结果，我们就可以获取结果并获得查询所需的确切数据。
        $queryResult = $this->onceWithColumns(
            is_null($key) ? [$column] : [$column, $key],
            function () {
                return $this->processor->processSelect(
                    $this, $this->runSelect()
                );
            }
        );

        if (empty($queryResult)) {
            return collect();
        }

        // If the columns are qualified with a table or have an alias, we cannot use
        // those directly in the "pluck" operations since the results from the DB
        // are only keyed by the column itself. We'll strip the table out here.
		// 如果列用表限定或有别名，我们就不能在"提取"操作中直接使用它们，
		// 因为数据库的结果只由列本身键入。我们把这张桌子剥光。
        $column = $this->stripTableForPluck($column);

        $key = $this->stripTableForPluck($key);

        return is_array($queryResult[0])
                    ? $this->pluckFromArrayColumn($queryResult, $column, $key)
                    : $this->pluckFromObjectColumn($queryResult, $column, $key);
    }

    /**
     * Strip off the table name or alias from a column identifier.
	 * 去掉表名或别名从列标识符中
     *
     * @param  string  $column
     * @return string|null
     */
    protected function stripTableForPluck($column)
    {
        if (is_null($column)) {
            return $column;
        }

        $separator = strpos(strtolower($column), ' as ') !== false ? ' as ' : '\.';

        return last(preg_split('~'.$separator.'~i', $column));
    }

    /**
     * Retrieve column values from rows represented as objects.
	 * 检索列值从表示为对象的行中
     *
     * @param  array  $queryResult
     * @param  string  $column
     * @param  string  $key
     * @return \Illuminate\Support\Collection
     */
    protected function pluckFromObjectColumn($queryResult, $column, $key)
    {
        $results = [];

        if (is_null($key)) {
            foreach ($queryResult as $row) {
                $results[] = $row->$column;
            }
        } else {
            foreach ($queryResult as $row) {
                $results[$row->$key] = $row->$column;
            }
        }

        return collect($results);
    }

    /**
     * Retrieve column values from rows represented as arrays.
	 * 检索列值从表示为数组的行中
     *
     * @param  array  $queryResult
     * @param  string  $column
     * @param  string  $key
     * @return \Illuminate\Support\Collection
     */
    protected function pluckFromArrayColumn($queryResult, $column, $key)
    {
        $results = [];

        if (is_null($key)) {
            foreach ($queryResult as $row) {
                $results[] = $row[$column];
            }
        } else {
            foreach ($queryResult as $row) {
                $results[$row[$key]] = $row[$column];
            }
        }

        return collect($results);
    }

    /**
     * Concatenate values of a given column as a string.
	 * 连接给定列的值为字符串
     *
     * @param  string  $column
     * @param  string  $glue
     * @return string
     */
    public function implode($column, $glue = '')
    {
        return $this->pluck($column)->implode($glue);
    }

    /**
     * Determine if any rows exist for the current query.
	 * 确定当前查询是否存在任何行
     *
     * @return bool
     */
    public function exists()
    {
        $results = $this->connection->select(
            $this->grammar->compileExists($this), $this->getBindings(), ! $this->useWritePdo
        );

        // If the results has rows, we will get the row and see if the exists column is a
        // boolean true. If there is no results for this query we will return false as
        // there are no rows for this query at all and we can return that info here.
		// 如果结果有行，我们将获得该行，并查看exists列是否为布尔值true。
		// 如果此查询没有结果，我们将返回false，因为此查询根本没有行，我们可以在此处返回该信息。
        if (isset($results[0])) {
            $results = (array) $results[0];

            return (bool) $results['exists'];
        }

        return false;
    }

    /**
     * Determine if no rows exist for the current query.
	 * 确定当前查询是否不存在行
     *
     * @return bool
     */
    public function doesntExist()
    {
        return ! $this->exists();
    }

    /**
     * Execute the given callback if no rows exist for the current query.
	 * 执行给定的回调，如果当前查询不存在行。
     *
     * @param  \Closure  $callback
     * @return mixed
     */
    public function existsOr(Closure $callback)
    {
        return $this->exists() ? true : $callback();
    }

    /**
     * Execute the given callback if rows exist for the current query.
	 * 如果当前查询存在行，则执行给定的回调。
     *
     * @param  \Closure  $callback
     * @return mixed
     */
    public function doesntExistOr(Closure $callback)
    {
        return $this->doesntExist() ? true : $callback();
    }

    /**
     * Retrieve the "count" result of the query.
	 * 检索查询的"count"结果
     *
     * @param  string  $columns
     * @return int
     */
    public function count($columns = '*')
    {
        return (int) $this->aggregate(__FUNCTION__, Arr::wrap($columns));
    }

    /**
     * Retrieve the minimum value of a given column.
	 * 检索给定列的最小值
     *
     * @param  string  $column
     * @return mixed
     */
    public function min($column)
    {
        return $this->aggregate(__FUNCTION__, [$column]);
    }

    /**
     * Retrieve the maximum value of a given column.
	 * 检索给定列的最大值
     *
     * @param  string  $column
     * @return mixed
     */
    public function max($column)
    {
        return $this->aggregate(__FUNCTION__, [$column]);
    }

    /**
     * Retrieve the sum of the values of a given column.
	 * 检索给定列的值之和
     *
     * @param  string  $column
     * @return mixed
     */
    public function sum($column)
    {
        $result = $this->aggregate(__FUNCTION__, [$column]);

        return $result ?: 0;
    }

    /**
     * Retrieve the average of the values of a given column.
	 * 检索给定列的值的平均值
     *
     * @param  string  $column
     * @return mixed
     */
    public function avg($column)
    {
        return $this->aggregate(__FUNCTION__, [$column]);
    }

    /**
     * Alias for the "avg" method.
	 * "avg"方法的别名
     *
     * @param  string  $column
     * @return mixed
     */
    public function average($column)
    {
        return $this->avg($column);
    }

    /**
     * Execute an aggregate function on the database.
	 * 执行聚合函数在数据库上
     *
     * @param  string  $function
     * @param  array  $columns
     * @return mixed
     */
    public function aggregate($function, $columns = ['*'])
    {
        $results = $this->cloneWithout($this->unions ? [] : ['columns'])
                        ->cloneWithoutBindings($this->unions ? [] : ['select'])
                        ->setAggregate($function, $columns)
                        ->get($columns);

        if (! $results->isEmpty()) {
            return array_change_key_case((array) $results[0])['aggregate'];
        }
    }

    /**
     * Execute a numeric aggregate function on the database.
	 * 执行一个数字聚合函数在数据库上
     *
     * @param  string  $function
     * @param  array  $columns
     * @return float|int
     */
    public function numericAggregate($function, $columns = ['*'])
    {
        $result = $this->aggregate($function, $columns);

        // If there is no result, we can obviously just return 0 here. Next, we will check
        // if the result is an integer or float. If it is already one of these two data
        // types we can just return the result as-is, otherwise we will convert this.
		// 如果没有结果，我们显然可以在这里返回0。接下来，我们将检查结果是整数还是浮点数。
		// 如果它已经是这两种数据类型之一，我们可以按原样返回结果，否则我们将转换它。
        if (! $result) {
            return 0;
        }

        if (is_int($result) || is_float($result)) {
            return $result;
        }

        // If the result doesn't contain a decimal place, we will assume it is an int then
        // cast it to one. When it does we will cast it to a float since it needs to be
        // cast to the expected data type for the developers out of pure convenience.
		// 如果结果不包含小数点，我们将假设它是一个int，然后将其转换为1。
		// 当它发生时，我们将把它转换为浮点数，因为纯粹为了方便起见，它需要转换为开发人员预期的数据类型。
        return strpos((string) $result, '.') === false
                ? (int) $result : (float) $result;
    }

    /**
     * Set the aggregate property without running the query.
	 * 设置聚合属性而不运行查询
     *
     * @param  string  $function
     * @param  array  $columns
     * @return $this
     */
    protected function setAggregate($function, $columns)
    {
        $this->aggregate = compact('function', 'columns');

        if (empty($this->groups)) {
            $this->orders = null;

            $this->bindings['order'] = [];
        }

        return $this;
    }

    /**
     * Execute the given callback while selecting the given columns.
	 * 在选择给定列时执行给定的回调
     *
     * After running the callback, the columns are reset to the original value.
     *
     * @param  array  $columns
     * @param  callable  $callback
     * @return mixed
     */
    protected function onceWithColumns($columns, $callback)
    {
        $original = $this->columns;

        if (is_null($original)) {
            $this->columns = $columns;
        }

        $result = $callback();

        $this->columns = $original;

        return $result;
    }

    /**
     * Insert a new record into the database.
	 * 插入一条新记录至数据库
     *
     * @param  array  $values
     * @return bool
     */
    public function insert(array $values)
    {
        // Since every insert gets treated like a batch insert, we will make sure the
        // bindings are structured in a way that is convenient when building these
        // inserts statements by verifying these elements are actually an array.
		// 由于每个插入都被视为批处理插入，
		// 我们将通过验证这些元素实际上是一个数组来确保绑定的结构在构建这些插入语句时是方便的。
        if (empty($values)) {
            return true;
        }

        if (! is_array(reset($values))) {
            $values = [$values];
        }

        // Here, we will sort the insert keys for every record so that each insert is
        // in the same order for the record. We need to make sure this is the case
        // so there are not any errors or problems when inserting these records.
		// 在这里，我们将对每条记录的插入键进行排序，以便每条插入对记录的顺序相同。
		// 我们需要确保情况确实如此，这样在插入这些记录时就不会出现任何错误或问题。
        else {
            foreach ($values as $key => $value) {
                ksort($value);

                $values[$key] = $value;
            }
        }

        // Finally, we will run this query against the database connection and return
        // the results. We will need to also flatten these bindings before running
        // the query so they are all in one huge, flattened array for execution.
		// 最后，我们将对数据库连接运行此查询并返回结果。
		// 在运行查询之前，我们还需要将这些绑定展平，以便它们都在一个巨大的展平数组中执行。
        return $this->connection->insert(
            $this->grammar->compileInsert($this, $values),
            $this->cleanBindings(Arr::flatten($values, 1))
        );
    }

    /**
     * Insert a new record into the database while ignoring errors.
	 * 插入一条新记录，同时忽略错误
     *
     * @param  array  $values
     * @return int
     */
    public function insertOrIgnore(array $values)
    {
        if (empty($values)) {
            return 0;
        }

        if (! is_array(reset($values))) {
            $values = [$values];
        } else {
            foreach ($values as $key => $value) {
                ksort($value);
                $values[$key] = $value;
            }
        }

        return $this->connection->affectingStatement(
            $this->grammar->compileInsertOrIgnore($this, $values),
            $this->cleanBindings(Arr::flatten($values, 1))
        );
    }

    /**
     * Insert a new record and get the value of the primary key.
	 * 插入一条新记录并获取主键的值
     *
     * @param  array  $values
     * @param  string|null  $sequence
     * @return int
     */
    public function insertGetId(array $values, $sequence = null)
    {
        $sql = $this->grammar->compileInsertGetId($this, $values, $sequence);

        $values = $this->cleanBindings($values);

        return $this->processor->processInsertGetId($this, $sql, $values, $sequence);
    }

    /**
     * Insert new records into the table using a subquery.
	 * 插入新记录至表中使用子查询
     *
     * @param  array  $columns
     * @param  \Closure|\Illuminate\Database\Query\Builder|string  $query
     * @return int
     */
    public function insertUsing(array $columns, $query)
    {
        [$sql, $bindings] = $this->createSub($query);

        return $this->connection->affectingStatement(
            $this->grammar->compileInsertUsing($this, $columns, $sql),
            $this->cleanBindings($bindings)
        );
    }

    /**
     * Update a record in the database.
	 * 更新一条记录在数据库
     *
     * @param  array  $values
     * @return int
     */
    public function update(array $values)
    {
        $sql = $this->grammar->compileUpdate($this, $values);

        return $this->connection->update($sql, $this->cleanBindings(
            $this->grammar->prepareBindingsForUpdate($this->bindings, $values)
        ));
    }

    /**
     * Insert or update a record matching the attributes, and fill it with values.
	 * 插入或更新与属性匹配的记录，并用值填充它
     *
     * @param  array  $attributes
     * @param  array  $values
     * @return bool
     */
    public function updateOrInsert(array $attributes, array $values = [])
    {
        if (! $this->where($attributes)->exists()) {
            return $this->insert(array_merge($attributes, $values));
        }

        if (empty($values)) {
            return true;
        }

        return (bool) $this->limit(1)->update($values);
    }

    /**
     * Increment a column's value by a given amount.
	 * 将列的值增加给定的量
     *
     * @param  string  $column
     * @param  float|int  $amount
     * @param  array  $extra
     * @return int
     *
     * @throws \InvalidArgumentException
     */
    public function increment($column, $amount = 1, array $extra = [])
    {
        if (! is_numeric($amount)) {
            throw new InvalidArgumentException('Non-numeric value passed to increment method.');
        }

        $wrapped = $this->grammar->wrap($column);

        $columns = array_merge([$column => $this->raw("$wrapped + $amount")], $extra);

        return $this->update($columns);
    }

    /**
     * Decrement a column's value by a given amount.
	 * 将列的值递减给定的量
     *
     * @param  string  $column
     * @param  float|int  $amount
     * @param  array  $extra
     * @return int
     *
     * @throws \InvalidArgumentException
     */
    public function decrement($column, $amount = 1, array $extra = [])
    {
        if (! is_numeric($amount)) {
            throw new InvalidArgumentException('Non-numeric value passed to decrement method.');
        }

        $wrapped = $this->grammar->wrap($column);

        $columns = array_merge([$column => $this->raw("$wrapped - $amount")], $extra);

        return $this->update($columns);
    }

    /**
     * Delete a record from the database.
	 * 删除一条记录从数据库
     *
     * @param  mixed  $id
     * @return int
     */
    public function delete($id = null)
    {
        // If an ID is passed to the method, we will set the where clause to check the
        // ID to let developers to simply and quickly remove a single row from this
        // database without manually specifying the "where" clauses on the query.
		// 如果将ID传递给该方法，我们将设置where子句来检查ID，
		// 以便开发人员可以简单快速地从此数据库中删除一行，而无需在查询中手动指定"where"子句。
        if (! is_null($id)) {
            $this->where($this->from.'.id', '=', $id);
        }

        return $this->connection->delete(
            $this->grammar->compileDelete($this), $this->cleanBindings(
                $this->grammar->prepareBindingsForDelete($this->bindings)
            )
        );
    }

    /**
     * Run a truncate statement on the table.
	 * 运行truncate语句在表上
     *
     * @return void
     */
    public function truncate()
    {
        foreach ($this->grammar->compileTruncate($this) as $sql => $bindings) {
            $this->connection->statement($sql, $bindings);
        }
    }

    /**
     * Get a new instance of the query builder.
	 * 得到查询创建器的新的实例
     *
     * @return \Illuminate\Database\Query\Builder
     */
    public function newQuery()
    {
        return new static($this->connection, $this->grammar, $this->processor);
    }

    /**
     * Create a new query instance for a sub-query.
	 * 创建新的查询实例为子查询
     *
     * @return \Illuminate\Database\Query\Builder
     */
    protected function forSubQuery()
    {
        return $this->newQuery();
    }

    /**
     * Create a raw database expression.
	 * 创建原始的数据库表达式
     *
     * @param  mixed  $value
     * @return \Illuminate\Database\Query\Expression
     */
    public function raw($value)
    {
        return $this->connection->raw($value);
    }

    /**
     * Get the current query value bindings in a flattened array.
	 * 得到当前查询值绑定在扁平数组中获取
     *
     * @return array
     */
    public function getBindings()
    {
        return Arr::flatten($this->bindings);
    }

    /**
     * Get the raw array of bindings.
	 * 得到绑定的原始数组
     *
     * @return array
     */
    public function getRawBindings()
    {
        return $this->bindings;
    }

    /**
     * Set the bindings on the query builder.
	 * 设置绑定在查询生成器上
     *
     * @param  array  $bindings
     * @param  string  $type
     * @return $this
     *
     * @throws \InvalidArgumentException
     */
    public function setBindings(array $bindings, $type = 'where')
    {
        if (! array_key_exists($type, $this->bindings)) {
            throw new InvalidArgumentException("Invalid binding type: {$type}.");
        }

        $this->bindings[$type] = $bindings;

        return $this;
    }

    /**
     * Add a binding to the query.
	 * 添加绑定至查询
     *
     * @param  mixed  $value
     * @param  string  $type
     * @return $this
     *
     * @throws \InvalidArgumentException
     */
    public function addBinding($value, $type = 'where')
    {
        if (! array_key_exists($type, $this->bindings)) {
            throw new InvalidArgumentException("Invalid binding type: {$type}.");
        }

        if (is_array($value)) {
            $this->bindings[$type] = array_values(array_merge($this->bindings[$type], $value));
        } else {
            $this->bindings[$type][] = $value;
        }

        return $this;
    }

    /**
     * Merge an array of bindings into our bindings.
	 * 合并绑定数组到我们的绑定中
     *
     * @param  \Illuminate\Database\Query\Builder  $query
     * @return $this
     */
    public function mergeBindings(self $query)
    {
        $this->bindings = array_merge_recursive($this->bindings, $query->bindings);

        return $this;
    }

    /**
     * Remove all of the expressions from a list of bindings.
	 * 删除所有表达式从绑定列表中
     *
     * @param  array  $bindings
     * @return array
     */
    protected function cleanBindings(array $bindings)
    {
        return array_values(array_filter($bindings, function ($binding) {
            return ! $binding instanceof Expression;
        }));
    }

    /**
     * Get a scalar type value from an unknown type of input.
	 * 得到标量类型值从未知类型的输入
     *
     * @param  mixed  $value
     * @return mixed
     */
    protected function flattenValue($value)
    {
        return is_array($value) ? head(Arr::flatten($value)) : $value;
    }

    /**
     * Get the default key name of the table.
	 * 得到表的默认键名
     *
     * @return string
     */
    protected function defaultKeyName()
    {
        return 'id';
    }

    /**
     * Get the database connection instance.
	 * 得到数据库连接实例
     *
     * @return \Illuminate\Database\ConnectionInterface
     */
    public function getConnection()
    {
        return $this->connection;
    }

    /**
     * Get the database query processor instance.
	 * 得到数据库查询处理器实例
     *
     * @return \Illuminate\Database\Query\Processors\Processor
     */
    public function getProcessor()
    {
        return $this->processor;
    }

    /**
     * Get the query grammar instance.
	 * 得到查询语法实例
     *
     * @return \Illuminate\Database\Query\Grammars\Grammar
     */
    public function getGrammar()
    {
        return $this->grammar;
    }

    /**
     * Use the write pdo for query.
	 * 使用写pdo对查询
     *
     * @return $this
     */
    public function useWritePdo()
    {
        $this->useWritePdo = true;

        return $this;
    }

    /**
     * Determine if the value is a query builder instance or a Closure.
	 * 确定该值是查询生成器实例还是Closure
     *
     * @param  mixed  $value
     * @return bool
     */
    protected function isQueryable($value)
    {
        return $value instanceof self ||
               $value instanceof EloquentBuilder ||
               $value instanceof Closure;
    }

    /**
     * Clone the query without the given properties.
	 * 克隆不含给定属性的查询
     *
     * @param  array  $properties
     * @return static
     */
    public function cloneWithout(array $properties)
    {
        return tap(clone $this, function ($clone) use ($properties) {
            foreach ($properties as $property) {
                $clone->{$property} = null;
            }
        });
    }

    /**
     * Clone the query without the given bindings.
	 * 克隆没有给定绑定的查询
     *
     * @param  array  $except
     * @return static
     */
    public function cloneWithoutBindings(array $except)
    {
        return tap(clone $this, function ($clone) use ($except) {
            foreach ($except as $type) {
                $clone->bindings[$type] = [];
            }
        });
    }

    /**
     * Dump the current SQL and bindings.
	 * 转储当前SQL和绑定
     *
     * @return $this
     */
    public function dump()
    {
        dump($this->toSql(), $this->getBindings());

        return $this;
    }

    /**
     * Die and dump the current SQL and bindings.
	 * 终止并转储当前SQL和绑定
     *
     * @return void
     */
    public function dd()
    {
        dd($this->toSql(), $this->getBindings());
    }

    /**
     * Handle dynamic method calls into the method.
	 * 处理动态方法调用
     *
     * @param  string  $method
     * @param  array  $parameters
     * @return mixed
     *
     * @throws \BadMethodCallException
     */
    public function __call($method, $parameters)
    {
        if (static::hasMacro($method)) {
            return $this->macroCall($method, $parameters);
        }

        if (Str::startsWith($method, 'where')) {
            return $this->dynamicWhere($method, $parameters);
        }

        static::throwBadMethodCallException($method);
    }
}

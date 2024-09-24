<?php
/**
 * 数据库，查询加入条款
 */

namespace Illuminate\Database\Query;

use Closure;

class JoinClause extends Builder
{
    /**
     * The type of join being performed.
	 * 要执行的连接类型
     *
     * @var string
     */
    public $type;

    /**
     * The table the join clause is joining to.
	 * 连接子句要连接的表
     *
     * @var string
     */
    public $table;

    /**
     * The connection of the parent query builder.
	 * 父查询生成器的连接
     *
     * @var \Illuminate\Database\ConnectionInterface
     */
    protected $parentConnection;

    /**
     * The grammar of the parent query builder.
	 * 父查询生成器的语法
     *
     * @var \Illuminate\Database\Query\Grammars\Grammar
     */
    protected $parentGrammar;

    /**
     * The processor of the parent query builder.
	 * 父查询生成器的处理器
     *
     * @var \Illuminate\Database\Query\Processors\Processor
     */
    protected $parentProcessor;

    /**
     * The class name of the parent query builder.
	 * 父查询生成器的类名
     *
     * @var string
     */
    protected $parentClass;

    /**
     * Create a new join clause instance.
	 * 创建新的连接子句实例
     *
     * @param  \Illuminate\Database\Query\Builder  $parentQuery
     * @param  string  $type
     * @param  string  $table
     * @return void
     */
    public function __construct(Builder $parentQuery, $type, $table)
    {
        $this->type = $type;
        $this->table = $table;
        $this->parentClass = get_class($parentQuery);
        $this->parentGrammar = $parentQuery->getGrammar();
        $this->parentProcessor = $parentQuery->getProcessor();
        $this->parentConnection = $parentQuery->getConnection();

        parent::__construct(
            $this->parentConnection, $this->parentGrammar, $this->parentProcessor
        );
    }

    /**
     * Add an "on" clause to the join.
	 * 向联接添加一个"on"子句
     *
     * On clauses can be chained, e.g.
     *
     *  $join->on('contacts.user_id', '=', 'users.id')
     *       ->on('contacts.info_id', '=', 'info.id')
     *
     * will produce the following SQL:
     *
     * on `contacts`.`user_id` = `users`.`id` and `contacts`.`info_id` = `info`.`id`
     *
     * @param  \Closure|string  $first
     * @param  string|null  $operator
     * @param  \Illuminate\Database\Query\Expression|string|null  $second
     * @param  string  $boolean
     * @return $this
     *
     * @throws \InvalidArgumentException
     */
    public function on($first, $operator = null, $second = null, $boolean = 'and')
    {
        if ($first instanceof Closure) {
            return $this->whereNested($first, $boolean);
        }

        return $this->whereColumn($first, $operator, $second, $boolean);
    }

    /**
     * Add an "or on" clause to the join.
	 * 向联接添加"或on"子句
     *
     * @param  \Closure|string  $first
     * @param  string|null  $operator
     * @param  string|null  $second
     * @return \Illuminate\Database\Query\JoinClause
     */
    public function orOn($first, $operator = null, $second = null)
    {
        return $this->on($first, $operator, $second, 'or');
    }

    /**
     * Get a new instance of the join clause builder.
	 * 得到连接子句构建器的新实例
     *
     * @return \Illuminate\Database\Query\JoinClause
     */
    public function newQuery()
    {
        return new static($this->newParentQuery(), $this->type, $this->table);
    }

    /**
     * Create a new query instance for sub-query.
	 * 创建新的查询实例为子查询
     *
     * @return \Illuminate\Database\Query\Builder
     */
    protected function forSubQuery()
    {
        return $this->newParentQuery()->newQuery();
    }

    /**
     * Create a new parent query instance.
	 * 创建新的父查询实例
     *
     * @return \Illuminate\Database\Query\Builder
     */
    protected function newParentQuery()
    {
        $class = $this->parentClass;

        return new $class($this->parentConnection, $this->parentGrammar, $this->parentProcessor);
    }
}

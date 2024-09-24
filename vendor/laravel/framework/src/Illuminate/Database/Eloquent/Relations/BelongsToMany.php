<?php
/**
 * 数据库，Eloquent从属许多
 */

namespace Illuminate\Database\Eloquent\Relations;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Str;
use InvalidArgumentException;

class BelongsToMany extends Relation
{
    use Concerns\InteractsWithPivotTable;

    /**
     * The intermediate table for the relation.
	 * 关系中间表
     *
     * @var string
     */
    protected $table;

    /**
     * The foreign key of the parent model.
	 * 模型的外键
     *
     * @var string
     */
    protected $foreignPivotKey;

    /**
     * The associated key of the relation.
	 * 外联键
     *
     * @var string
     */
    protected $relatedPivotKey;

    /**
     * The key name of the parent model.
	 * 父模型键名
     *
     * @var string
     */
    protected $parentKey;

    /**
     * The key name of the related model.
	 * 相关模型键名
     *
     * @var string
     */
    protected $relatedKey;

    /**
     * The "name" of the relationship.
	 * 关联名称
     *
     * @var string
     */
    protected $relationName;

    /**
     * The pivot table columns to retrieve.
	 * 要检索的数据透视表列
     *
     * @var array
     */
    protected $pivotColumns = [];

    /**
     * Any pivot table restrictions for where clauses.
	 * where子句的透视表限制
     *
     * @var array
     */
    protected $pivotWheres = [];

    /**
     * Any pivot table restrictions for whereIn clauses.
	 * where子句的数据透视表限制
     *
     * @var array
     */
    protected $pivotWhereIns = [];

    /**
     * The default values for the pivot columns.
	 * 透视列的默认值
     *
     * @var array
     */
    protected $pivotValues = [];

    /**
     * Indicates if timestamps are available on the pivot table.
	 * 指明数据透视表上是否有可用的时间戳
     *
     * @var bool
     */
    public $withTimestamps = false;

    /**
     * The custom pivot table column for the created_at timestamp.
	 * 自定义数据透视表列为created_at时间戳
     *
     * @var string
     */
    protected $pivotCreatedAt;

    /**
     * The custom pivot table column for the updated_at timestamp.
	 * 自定义数据透视表列为updated_at时间戳
     *
     * @var string
     */
    protected $pivotUpdatedAt;

    /**
     * The class name of the custom pivot model to use for the relationship.
	 * 自定义数据透视模型的类名用于关系的
     *
     * @var string
     */
    protected $using;

    /**
     * The name of the accessor to use for the "pivot" relationship.
	 * 要用于"支点"关系的访问器的名称
     *
     * @var string
     */
    protected $accessor = 'pivot';

    /**
     * The count of self joins.
	 * 自连接计数
     *
     * @var int
     */
    protected static $selfJoinCount = 0;

    /**
     * Create a new belongs to many relationship instance.
	 * 创建新的属于多个关系实例
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  \Illuminate\Database\Eloquent\Model  $parent
     * @param  string  $table
     * @param  string  $foreignPivotKey
     * @param  string  $relatedPivotKey
     * @param  string  $parentKey
     * @param  string  $relatedKey
     * @param  string  $relationName
     * @return void
     */
    public function __construct(Builder $query, Model $parent, $table, $foreignPivotKey,
                                $relatedPivotKey, $parentKey, $relatedKey, $relationName = null)
    {
        $this->parentKey = $parentKey;
        $this->relatedKey = $relatedKey;
        $this->relationName = $relationName;
        $this->relatedPivotKey = $relatedPivotKey;
        $this->foreignPivotKey = $foreignPivotKey;
        $this->table = $this->resolveTableName($table);

        parent::__construct($query, $parent);
    }

    /**
     * Attempt to resolve the intermediate table name from the given string.
	 * 尝试解析中间表名从给定字符串中
     *
     * @param  string  $table
     * @return string
     */
    protected function resolveTableName($table)
    {
        if (! Str::contains($table, '\\') || ! class_exists($table)) {
            return $table;
        }

        $model = new $table;

        if (! $model instanceof Model) {
            return $table;
        }

        if ($model instanceof Pivot) {
            $this->using($table);
        }

        return $model->getTable();
    }

    /**
     * Set the base constraints on the relation query.
	 * 设置基本约束在关系查询上
     *
     * @return void
     */
    public function addConstraints()
    {
        $this->performJoin();

        if (static::$constraints) {
            $this->addWhereConstraints();
        }
    }

    /**
     * Set the join clause for the relation query.
	 * 设置连接子句为关系查询
     *
     * @param  \Illuminate\Database\Eloquent\Builder|null  $query
     * @return $this
     */
    protected function performJoin($query = null)
    {
        $query = $query ?: $this->query;

        // We need to join to the intermediate table on the related model's primary
        // key column with the intermediate table's foreign key for the related
        // model instance. Then we can set the "where" for the parent models.
		// 我们需要使用相关实例的中间表的外键连接到相关模型主键列上的中间表。
		// 然后，我们可以为父模型设置“位置”。
        $baseTable = $this->related->getTable();

        $key = $baseTable.'.'.$this->relatedKey;

        $query->join($this->table, $key, '=', $this->getQualifiedRelatedPivotKeyName());

        return $this;
    }

    /**
     * Set the where clause for the relation query.
	 * 设置where子句为关系查询
     *
     * @return $this
     */
    protected function addWhereConstraints()
    {
        $this->query->where(
            $this->getQualifiedForeignPivotKeyName(), '=', $this->parent->{$this->parentKey}
        );

        return $this;
    }

    /**
     * Set the constraints for an eager load of the relation.
	 * 为关系的即时加载设置约束
     *
     * @param  array  $models
     * @return void
     */
    public function addEagerConstraints(array $models)
    {
        $whereIn = $this->whereInMethod($this->parent, $this->parentKey);

        $this->query->{$whereIn}(
            $this->getQualifiedForeignPivotKeyName(),
            $this->getKeys($models, $this->parentKey)
        );
    }

    /**
     * Initialize the relation on a set of models.
	 * 初始化一组模型上的关系
     *
     * @param  array  $models
     * @param  string  $relation
     * @return array
     */
    public function initRelation(array $models, $relation)
    {
        foreach ($models as $model) {
            $model->setRelation($relation, $this->related->newCollection());
        }

        return $models;
    }

    /**
     * Match the eagerly loaded results to their parents.
	 * 匹配急切加载的结果与他们的父母
     *
     * @param  array  $models
     * @param  \Illuminate\Database\Eloquent\Collection  $results
     * @param  string  $relation
     * @return array
     */
    public function match(array $models, Collection $results, $relation)
    {
        $dictionary = $this->buildDictionary($results);

        // Once we have an array dictionary of child objects we can easily match the
        // children back to their parent using the dictionary and the keys on the
        // the parent models. Then we will return the hydrated models back out.
		// 一旦我们有了子对象的数组字典，我们就可以使用字典和父模型上的键轻松地将子对象与父对象进行匹配。
		// 然后我们将把水合模型放回原处。
        foreach ($models as $model) {
            if (isset($dictionary[$key = $model->{$this->parentKey}])) {
                $model->setRelation(
                    $relation, $this->related->newCollection($dictionary[$key])
                );
            }
        }

        return $models;
    }

    /**
     * Build model dictionary keyed by the relation's foreign key.
	 * 构建以关系的外键为键的模型字典
     *
     * @param  \Illuminate\Database\Eloquent\Collection  $results
     * @return array
     */
    protected function buildDictionary(Collection $results)
    {
        // First we will build a dictionary of child models keyed by the foreign key
        // of the relation so that we will easily and quickly match them to their
        // parents without having a possibly slow inner loops for every models.
		// 首先，我们将建立一个由关系的外键键控的子模型字典，
		// 这样我们就可以轻松快速地将它们与它们的父母匹配，而不会对每个模型产生可能缓慢的内部循环。
        $dictionary = [];

        foreach ($results as $result) {
            $dictionary[$result->{$this->accessor}->{$this->foreignPivotKey}][] = $result;
        }

        return $dictionary;
    }

    /**
     * Get the class being used for pivot models.
	 * 得到用于pivot模型的类
     *
     * @return string
     */
    public function getPivotClass()
    {
        return $this->using ?? Pivot::class;
    }

    /**
     * Specify the custom pivot model to use for the relationship.
	 * 指定要用于关系的自定义数据透视模型
     *
     * @param  string  $class
     * @return $this
     */
    public function using($class)
    {
        $this->using = $class;

        return $this;
    }

    /**
     * Specify the custom pivot accessor to use for the relationship.
	 * 指定要用于关系的自定义数据透视访问器
     *
     * @param  string  $accessor
     * @return $this
     */
    public function as($accessor)
    {
        $this->accessor = $accessor;

        return $this;
    }

    /**
     * Set a where clause for a pivot table column.
	 * 设置where子句为数据透视表列
     *
     * @param  string  $column
     * @param  string|null  $operator
     * @param  mixed  $value
     * @param  string  $boolean
     * @return $this
     */
    public function wherePivot($column, $operator = null, $value = null, $boolean = 'and')
    {
        $this->pivotWheres[] = func_get_args();

        return $this->where($this->table.'.'.$column, $operator, $value, $boolean);
    }

    /**
     * Set a "where in" clause for a pivot table column.
	 * 设置"where in"子句为数据透视表列
     *
     * @param  string  $column
     * @param  mixed  $values
     * @param  string  $boolean
     * @param  bool  $not
     * @return $this
     */
    public function wherePivotIn($column, $values, $boolean = 'and', $not = false)
    {
        $this->pivotWhereIns[] = func_get_args();

        return $this->whereIn($this->table.'.'.$column, $values, $boolean, $not);
    }

    /**
     * Set an "or where" clause for a pivot table column.
	 * 设置"or where"子句为数据透视表列
     *
     * @param  string  $column
     * @param  string|null  $operator
     * @param  mixed  $value
     * @return $this
     */
    public function orWherePivot($column, $operator = null, $value = null)
    {
        return $this->wherePivot($column, $operator, $value, 'or');
    }

    /**
     * Set a where clause for a pivot table column.
	 * 设置where子句为数据透视表列
     *
     * In addition, new pivot records will receive this value.
     *
     * @param  string|array  $column
     * @param  mixed  $value
     * @return $this
     *
     * @throws \InvalidArgumentException
     */
    public function withPivotValue($column, $value = null)
    {
        if (is_array($column)) {
            foreach ($column as $name => $value) {
                $this->withPivotValue($name, $value);
            }

            return $this;
        }

        if (is_null($value)) {
            throw new InvalidArgumentException('The provided value may not be null.');
        }

        $this->pivotValues[] = compact('column', 'value');

        return $this->wherePivot($column, '=', $value);
    }

    /**
     * Set an "or where in" clause for a pivot table column.
	 * 设置"or where in"子句为数据透视表列
     *
     * @param  string  $column
     * @param  mixed  $values
     * @return $this
     */
    public function orWherePivotIn($column, $values)
    {
        return $this->wherePivotIn($column, $values, 'or');
    }

    /**
     * Set a "where not in" clause for a pivot table column.
	 * 设置"where not in"子句为数据透视表列
     *
     * @param  string  $column
     * @param  mixed  $values
     * @param  string  $boolean
     * @return $this
     */
    public function wherePivotNotIn($column, $values, $boolean = 'and')
    {
        return $this->wherePivotIn($column, $values, $boolean, true);
    }

    /**
     * Set an "or where not in" clause for a pivot table column.
	 * 设置"or where not in"子句为数据透视表列
     *
     * @param  string  $column
     * @param  mixed  $values
     * @return $this
     */
    public function orWherePivotNotIn($column, $values)
    {
        return $this->wherePivotNotIn($column, $values, 'or');
    }

    /**
     * Find a related model by its primary key or return new instance of the related model.
	 * 通过主键查找相关模型或返回相关模型的新实例
     *
     * @param  mixed  $id
     * @param  array  $columns
     * @return \Illuminate\Support\Collection|\Illuminate\Database\Eloquent\Model
     */
    public function findOrNew($id, $columns = ['*'])
    {
        if (is_null($instance = $this->find($id, $columns))) {
            $instance = $this->related->newInstance();
        }

        return $instance;
    }

    /**
     * Get the first related model record matching the attributes or instantiate it.
	 * 得到与属性匹配的第一个相关模型记录，或者实例化它。
     *
     * @param  array  $attributes
     * @return \Illuminate\Database\Eloquent\Model
     */
    public function firstOrNew(array $attributes)
    {
        if (is_null($instance = $this->where($attributes)->first())) {
            $instance = $this->related->newInstance($attributes);
        }

        return $instance;
    }

    /**
     * Get the first related record matching the attributes or create it.
	 * 得到匹配属性的第一个相关记录，或者创建它。
     *
     * @param  array  $attributes
     * @param  array  $joining
     * @param  bool  $touch
     * @return \Illuminate\Database\Eloquent\Model
     */
    public function firstOrCreate(array $attributes, array $joining = [], $touch = true)
    {
        if (is_null($instance = $this->where($attributes)->first())) {
            $instance = $this->create($attributes, $joining, $touch);
        }

        return $instance;
    }

    /**
     * Create or update a related record matching the attributes, and fill it with values.
	 * 创建或更新与属性匹配的相关记录，并用值填充它。
     *
     * @param  array  $attributes
     * @param  array  $values
     * @param  array  $joining
     * @param  bool  $touch
     * @return \Illuminate\Database\Eloquent\Model
     */
    public function updateOrCreate(array $attributes, array $values = [], array $joining = [], $touch = true)
    {
        if (is_null($instance = $this->where($attributes)->first())) {
            return $this->create($values, $joining, $touch);
        }

        $instance->fill($values);

        $instance->save(['touch' => false]);

        return $instance;
    }

    /**
     * Find a related model by its primary key.
	 * 查找相关模型根据主键
     *
     * @param  mixed  $id
     * @param  array  $columns
     * @return \Illuminate\Database\Eloquent\Model|\Illuminate\Database\Eloquent\Collection|null
     */
    public function find($id, $columns = ['*'])
    {
        return is_array($id) ? $this->findMany($id, $columns) : $this->where(
            $this->getRelated()->getQualifiedKeyName(), '=', $this->parseId($id)
        )->first($columns);
    }

    /**
     * Find multiple related models by their primary keys.
	 * 查找多个相关模型通过主键
     *
     * @param  mixed  $ids
     * @param  array  $columns
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function findMany($ids, $columns = ['*'])
    {
        return empty($ids) ? $this->getRelated()->newCollection() : $this->whereIn(
            $this->getRelated()->getQualifiedKeyName(), $this->parseIds($ids)
        )->get($columns);
    }

    /**
     * Find a related model by its primary key or throw an exception.
	 * 根据主键查找相关模型，否则抛出异常。
     *
     * @param  mixed  $id
     * @param  array  $columns
     * @return \Illuminate\Database\Eloquent\Model|\Illuminate\Database\Eloquent\Collection
     *
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     */
    public function findOrFail($id, $columns = ['*'])
    {
        $result = $this->find($id, $columns);

        if (is_array($id)) {
            if (count($result) === count(array_unique($id))) {
                return $result;
            }
        } elseif (! is_null($result)) {
            return $result;
        }

        throw (new ModelNotFoundException)->setModel(get_class($this->related), $id);
    }

    /**
     * Add a basic where clause to the query, and return the first result.
	 * 向查询添加一个基本的where子句，并返回第一个结果。
     *
     * @param  \Closure|string|array  $column
     * @param  mixed  $operator
     * @param  mixed  $value
     * @param  string  $boolean
     * @return \Illuminate\Database\Eloquent\Model|static
     */
    public function firstWhere($column, $operator = null, $value = null, $boolean = 'and')
    {
        return $this->where($column, $operator, $value, $boolean)->first();
    }

    /**
     * Execute the query and get the first result.
	 * 执行查询并获得第一个结果
     *
     * @param  array  $columns
     * @return mixed
     */
    public function first($columns = ['*'])
    {
        $results = $this->take(1)->get($columns);

        return count($results) > 0 ? $results->first() : null;
    }

    /**
     * Execute the query and get the first result or throw an exception.
	 * 执行查询并获得第一个结果或抛出异常
     *
     * @param  array  $columns
     * @return \Illuminate\Database\Eloquent\Model|static
     *
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     */
    public function firstOrFail($columns = ['*'])
    {
        if (! is_null($model = $this->first($columns))) {
            return $model;
        }

        throw (new ModelNotFoundException)->setModel(get_class($this->related));
    }

    /**
     * Get the results of the relationship.
	 * 得到关系的结果
     *
     * @return mixed
     */
    public function getResults()
    {
        return ! is_null($this->parent->{$this->parentKey})
                ? $this->get()
                : $this->related->newCollection();
    }

    /**
     * Execute the query as a "select" statement.
	 * 执行查询以"select"语句的形式
     *
     * @param  array  $columns
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function get($columns = ['*'])
    {
        // First we'll add the proper select columns onto the query so it is run with
        // the proper columns. Then, we will get the results and hydrate out pivot
        // models with the result of those columns as a separate model relation.
		// 首先，我们将在查询中添加正确的选择列，以便使用正确的列运行查询。
		// 然后，我们将得到结果，并将这些柱的结果作为单独的模型关系来水合枢轴模型。
        $builder = $this->query->applyScopes();

        $columns = $builder->getQuery()->columns ? [] : $columns;

        $models = $builder->addSelect(
            $this->shouldSelect($columns)
        )->getModels();

        $this->hydratePivotRelation($models);

        // If we actually found models we will also eager load any relationships that
        // have been specified as needing to be eager loaded. This will solve the
        // n + 1 query problem for the developer and also increase performance.
		// 如果我们真的找到了模型，我们也会渴望加载任何被指定为需要渴望加载的关系。
		// 这将为开发人员解决n+1查询问题，并提高性能。
        if (count($models) > 0) {
            $models = $builder->eagerLoadRelations($models);
        }

        return $this->related->newCollection($models);
    }

    /**
     * Get the select columns for the relation query.
	 * 得到关系查询的选择列
     *
     * @param  array  $columns
     * @return array
     */
    protected function shouldSelect(array $columns = ['*'])
    {
        if ($columns == ['*']) {
            $columns = [$this->related->getTable().'.*'];
        }

        return array_merge($columns, $this->aliasedPivotColumns());
    }

    /**
     * Get the pivot columns for the relation.
	 * 得到关系的主列
     *
     * "pivot_" is prefixed ot each column for easy removal later.
     *
     * @return array
     */
    protected function aliasedPivotColumns()
    {
        $defaults = [$this->foreignPivotKey, $this->relatedPivotKey];

        return collect(array_merge($defaults, $this->pivotColumns))->map(function ($column) {
            return $this->table.'.'.$column.' as pivot_'.$column;
        })->unique()->all();
    }

    /**
     * Get a paginator for the "select" statement.
	 * 得到"select"语句的分页器
     *
     * @param  int|null  $perPage
     * @param  array  $columns
     * @param  string  $pageName
     * @param  int|null  $page
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    public function paginate($perPage = null, $columns = ['*'], $pageName = 'page', $page = null)
    {
        $this->query->addSelect($this->shouldSelect($columns));

        return tap($this->query->paginate($perPage, $columns, $pageName, $page), function ($paginator) {
            $this->hydratePivotRelation($paginator->items());
        });
    }

    /**
     * Paginate the given query into a simple paginator.
	 * 将给定查询分页到一个简单的分页器中
     *
     * @param  int|null  $perPage
     * @param  array  $columns
     * @param  string  $pageName
     * @param  int|null  $page
     * @return \Illuminate\Contracts\Pagination\Paginator
     */
    public function simplePaginate($perPage = null, $columns = ['*'], $pageName = 'page', $page = null)
    {
        $this->query->addSelect($this->shouldSelect($columns));

        return tap($this->query->simplePaginate($perPage, $columns, $pageName, $page), function ($paginator) {
            $this->hydratePivotRelation($paginator->items());
        });
    }

    /**
     * Chunk the results of the query.
	 * 将查询的结果分块
     *
     * @param  int  $count
     * @param  callable  $callback
     * @return bool
     */
    public function chunk($count, callable $callback)
    {
        $this->query->addSelect($this->shouldSelect());

        return $this->query->chunk($count, function ($results) use ($callback) {
            $this->hydratePivotRelation($results->all());

            return $callback($results);
        });
    }

    /**
     * Chunk the results of a query by comparing numeric IDs.
	 * 通过比较数字id对查询结果进行分组
     *
     * @param  int  $count
     * @param  callable  $callback
     * @param  string|null  $column
     * @param  string|null  $alias
     * @return bool
     */
    public function chunkById($count, callable $callback, $column = null, $alias = null)
    {
        $this->query->addSelect($this->shouldSelect());

        $column = $column ?? $this->getRelated()->qualifyColumn(
            $this->getRelatedKeyName()
        );

        $alias = $alias ?? $this->getRelatedKeyName();

        return $this->query->chunkById($count, function ($results) use ($callback) {
            $this->hydratePivotRelation($results->all());

            return $callback($results);
        }, $column, $alias);
    }

    /**
     * Execute a callback over each item while chunking.
	 * 在分块时对每个项执行回调
     *
     * @param  callable  $callback
     * @param  int  $count
     * @return bool
     */
    public function each(callable $callback, $count = 1000)
    {
        return $this->chunk($count, function ($results) use ($callback) {
            foreach ($results as $key => $value) {
                if ($callback($value, $key) === false) {
                    return false;
                }
            }
        });
    }

    /**
     * Get a lazy collection for the given query.
	 * 得到给定查询的惰性集合
     *
     * @return \Illuminate\Support\LazyCollection
     */
    public function cursor()
    {
        $this->query->addSelect($this->shouldSelect());

        return $this->query->cursor()->map(function ($model) {
            $this->hydratePivotRelation([$model]);

            return $model;
        });
    }

    /**
     * Hydrate the pivot table relationship on the models.
	 * 在模型上建立透视表关系
     *
     * @param  array  $models
     * @return void
     */
    protected function hydratePivotRelation(array $models)
    {
        // To hydrate the pivot relationship, we will just gather the pivot attributes
        // and create a new Pivot model, which is basically a dynamic model that we
        // will set the attributes, table, and connections on it so it will work.
		// 为了优化枢轴关系，我们只需收集枢轴属性并创建一个新的枢轴模型，
		// 这基本上是一个动态模型，我们将在其上设置属性、表和连接，使其正常工作。
        foreach ($models as $model) {
            $model->setRelation($this->accessor, $this->newExistingPivot(
                $this->migratePivotAttributes($model)
            ));
        }
    }

    /**
     * Get the pivot attributes from a model.
	 * 得到枢轴属性从模型中
     *
     * @param  \Illuminate\Database\Eloquent\Model  $model
     * @return array
     */
    protected function migratePivotAttributes(Model $model)
    {
        $values = [];

        foreach ($model->getAttributes() as $key => $value) {
            // To get the pivots attributes we will just take any of the attributes which
            // begin with "pivot_" and add those to this arrays, as well as unsetting
            // them from the parent's models since they exist in a different table.
			// 为了获取枢轴属性，我们只需获取以“pivot_”开头的任何属性，并将其添加到此数组中，
			// 同时将其从父模型中取消设置，因为它们存在于不同的表中。
            if (strpos($key, 'pivot_') === 0) {
                $values[substr($key, 6)] = $value;

                unset($model->$key);
            }
        }

        return $values;
    }

    /**
     * If we're touching the parent model, touch.
	 * 如果我们要触摸父模型，触摸。
     *
     * @return void
     */
    public function touchIfTouching()
    {
        if ($this->touchingParent()) {
            $this->getParent()->touch();
        }

        if ($this->getParent()->touches($this->relationName)) {
            $this->touch();
        }
    }

    /**
     * Determine if we should touch the parent on sync.
	 * 确定我们是否应该在同步时触摸父节点
     *
     * @return bool
     */
    protected function touchingParent()
    {
        return $this->getRelated()->touches($this->guessInverseRelation());
    }

    /**
     * Attempt to guess the name of the inverse of the relation.
	 * 尝试猜测关系的逆的名称
     *
     * @return string
     */
    protected function guessInverseRelation()
    {
        return Str::camel(Str::pluralStudly(class_basename($this->getParent())));
    }

    /**
     * Touch all of the related models for the relationship.
	 * 触摸关系的所有相关模型
     *
     * E.g.: Touch all roles associated with this user.
     *
     * @return void
     */
    public function touch()
    {
        $key = $this->getRelated()->getKeyName();

        $columns = [
            $this->related->getUpdatedAtColumn() => $this->related->freshTimestampString(),
        ];

        // If we actually have IDs for the relation, we will run the query to update all
        // the related model's timestamps, to make sure these all reflect the changes
        // to the parent models. This will help us keep any caching synced up here.
		// 如果我们确实有关系的ID，我们将运行查询来更新所有相关模型的时间戳，
		// 以确保这些时间戳都反映了对父模型的更改。这将有助于我们在此处保持任何缓存的同步。
        if (count($ids = $this->allRelatedIds()) > 0) {
            $this->getRelated()->newQueryWithoutRelationships()->whereIn($key, $ids)->update($columns);
        }
    }

    /**
     * Get all of the IDs for the related models.
	 * 得到相关模型的所有IDs
     *
     * @return \Illuminate\Support\Collection
     */
    public function allRelatedIds()
    {
        return $this->newPivotQuery()->pluck($this->relatedPivotKey);
    }

    /**
     * Save a new model and attach it to the parent model.
	 * 保存一个新模型并将其附加到父模型
     *
     * @param  \Illuminate\Database\Eloquent\Model  $model
     * @param  array  $pivotAttributes
     * @param  bool  $touch
     * @return \Illuminate\Database\Eloquent\Model
     */
    public function save(Model $model, array $pivotAttributes = [], $touch = true)
    {
        $model->save(['touch' => false]);

        $this->attach($model, $pivotAttributes, $touch);

        return $model;
    }

    /**
     * Save an array of new models and attach them to the parent model.
	 * 保存一组新模型，并将它们附加到父模型上。
     *
     * @param  \Illuminate\Support\Collection|array  $models
     * @param  array  $pivotAttributes
     * @return array
     */
    public function saveMany($models, array $pivotAttributes = [])
    {
        foreach ($models as $key => $model) {
            $this->save($model, (array) ($pivotAttributes[$key] ?? []), false);
        }

        $this->touchIfTouching();

        return $models;
    }

    /**
     * Create a new instance of the related model.
	 * 创建相关模型的新实例
     *
     * @param  array  $attributes
     * @param  array  $joining
     * @param  bool  $touch
     * @return \Illuminate\Database\Eloquent\Model
     */
    public function create(array $attributes = [], array $joining = [], $touch = true)
    {
        $instance = $this->related->newInstance($attributes);

        // Once we save the related model, we need to attach it to the base model via
        // through intermediate table so we'll use the existing "attach" method to
        // accomplish this which will insert the record and any more attributes.
		// 一旦我们保存了相关模型，我们需要通过中间表将其附加到基础模型上，
		// 因此我们将使用现有的"附加"方法来实现这一点，该方法将插入记录和任何其他属性。
        $instance->save(['touch' => false]);

        $this->attach($instance, $joining, $touch);

        return $instance;
    }

    /**
     * Create an array of new instances of the related models.
	 * 创建相关模型的新实例数组
     *
     * @param  iterable  $records
     * @param  array  $joinings
     * @return array
     */
    public function createMany(iterable $records, array $joinings = [])
    {
        $instances = [];

        foreach ($records as $key => $record) {
            $instances[] = $this->create($record, (array) ($joinings[$key] ?? []), false);
        }

        $this->touchIfTouching();

        return $instances;
    }

    /**
     * Add the constraints for a relationship query.
	 * 添加约束为关系查询
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  \Illuminate\Database\Eloquent\Builder  $parentQuery
     * @param  array|mixed  $columns
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function getRelationExistenceQuery(Builder $query, Builder $parentQuery, $columns = ['*'])
    {
        if ($parentQuery->getQuery()->from == $query->getQuery()->from) {
            return $this->getRelationExistenceQueryForSelfJoin($query, $parentQuery, $columns);
        }

        $this->performJoin($query);

        return parent::getRelationExistenceQuery($query, $parentQuery, $columns);
    }

    /**
     * Add the constraints for a relationship query on the same table.
	 * 添加约束为同一表上的关系查询
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  \Illuminate\Database\Eloquent\Builder  $parentQuery
     * @param  array|mixed  $columns
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function getRelationExistenceQueryForSelfJoin(Builder $query, Builder $parentQuery, $columns = ['*'])
    {
        $query->select($columns);

        $query->from($this->related->getTable().' as '.$hash = $this->getRelationCountHash());

        $this->related->setTable($hash);

        $this->performJoin($query);

        return parent::getRelationExistenceQuery($query, $parentQuery, $columns);
    }

    /**
     * Get the key for comparing against the parent key in "has" query.
	 * 得到用于与"has"查询中的父键进行比较的键。
     *
     * @return string
     */
    public function getExistenceCompareKey()
    {
        return $this->getQualifiedForeignPivotKeyName();
    }

    /**
     * Get a relationship join table hash.
	 * 得到关系连接表散列
     *
     * @return string
     */
    public function getRelationCountHash()
    {
        return 'laravel_reserved_'.static::$selfJoinCount++;
    }

    /**
     * Specify that the pivot table has creation and update timestamps.
	 * 指定数据透视表具有创建和更新时间戳
     *
     * @param  mixed  $createdAt
     * @param  mixed  $updatedAt
     * @return $this
     */
    public function withTimestamps($createdAt = null, $updatedAt = null)
    {
        $this->withTimestamps = true;

        $this->pivotCreatedAt = $createdAt;
        $this->pivotUpdatedAt = $updatedAt;

        return $this->withPivot($this->createdAt(), $this->updatedAt());
    }

    /**
     * Get the name of the "created at" column.
	 * 得到"创建位置"列的名称
     *
     * @return string
     */
    public function createdAt()
    {
        return $this->pivotCreatedAt ?: $this->parent->getCreatedAtColumn();
    }

    /**
     * Get the name of the "updated at" column.
	 * 得到"更新时间"列的名称
     *
     * @return string
     */
    public function updatedAt()
    {
        return $this->pivotUpdatedAt ?: $this->parent->getUpdatedAtColumn();
    }

    /**
     * Get the foreign key for the relation.
	 * 得到关系的外键
     *
     * @return string
     */
    public function getForeignPivotKeyName()
    {
        return $this->foreignPivotKey;
    }

    /**
     * Get the fully qualified foreign key for the relation.
	 * 得到关系的完全限定外键
     *
     * @return string
     */
    public function getQualifiedForeignPivotKeyName()
    {
        return $this->table.'.'.$this->foreignPivotKey;
    }

    /**
     * Get the "related key" for the relation.
	 * 得到该关系的"相关键"
     *
     * @return string
     */
    public function getRelatedPivotKeyName()
    {
        return $this->relatedPivotKey;
    }

    /**
     * Get the fully qualified "related key" for the relation.
	 * 得到关系的完全限定"相关键"
     *
     * @return string
     */
    public function getQualifiedRelatedPivotKeyName()
    {
        return $this->table.'.'.$this->relatedPivotKey;
    }

    /**
     * Get the parent key for the relationship.
	 * 得到关系的父键
     *
     * @return string
     */
    public function getParentKeyName()
    {
        return $this->parentKey;
    }

    /**
     * Get the fully qualified parent key name for the relation.
	 * 得到关系的完全限定父键名
     *
     * @return string
     */
    public function getQualifiedParentKeyName()
    {
        return $this->parent->qualifyColumn($this->parentKey);
    }

    /**
     * Get the related key for the relationship.
	 * 得到关系的相关键
     *
     * @return string
     */
    public function getRelatedKeyName()
    {
        return $this->relatedKey;
    }

    /**
     * Get the intermediate table for the relationship.
	 * 得到关系的中间表
     *
     * @return string
     */
    public function getTable()
    {
        return $this->table;
    }

    /**
     * Get the relationship name for the relationship.
	 * 得到关系的关系名称
     *
     * @return string
     */
    public function getRelationName()
    {
        return $this->relationName;
    }

    /**
     * Get the name of the pivot accessor for this relationship.
	 * 得到此关系的数据透视访问器的名称
     *
     * @return string
     */
    public function getPivotAccessor()
    {
        return $this->accessor;
    }

    /**
     * Get the pivot columns for this relationship.
	 * 得到这个关系的主列
     *
     * @return array
     */
    public function getPivotColumns()
    {
        return $this->pivotColumns;
    }
}

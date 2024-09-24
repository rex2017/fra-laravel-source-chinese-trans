<?php
/**
 * 数据库，Eloquent转变
 */

namespace Illuminate\Database\Eloquent\Relations;

use BadMethodCallException;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

class MorphTo extends BelongsTo
{
    /**
     * The type of the polymorphic relation.
	 * 多态关系的类型
     *
     * @var string
     */
    protected $morphType;

    /**
     * The models whose relations are being eager loaded.
	 * 被加载模型
     *
     * @var \Illuminate\Database\Eloquent\Collection
     */
    protected $models;

    /**
     * All of the models keyed by ID.
	 * 模型词典
     *
     * @var array
     */
    protected $dictionary = [];

    /**
     * A buffer of dynamic calls to query macros.
	 * 用于动态调用查询宏的缓冲区
     *
     * @var array
     */
    protected $macroBuffer = [];

    /**
     * A map of relations to load for each individual morph type.
	 * 要为每个单独的变形类型加载的关系映射
     *
     * @var array
     */
    protected $morphableEagerLoads = [];

    /**
     * Create a new morph to relationship instance.
	 * 创建关系实例的新变形
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  \Illuminate\Database\Eloquent\Model  $parent
     * @param  string  $foreignKey
     * @param  string  $ownerKey
     * @param  string  $type
     * @param  string  $relation
     * @return void
     */
    public function __construct(Builder $query, Model $parent, $foreignKey, $ownerKey, $type, $relation)
    {
        $this->morphType = $type;

        parent::__construct($query, $parent, $foreignKey, $ownerKey, $relation);
    }

    /**
     * Set the constraints for an eager load of the relation.
	 * 设置约束为关系的即时加载
     *
     * @param  array  $models
     * @return void
     */
    public function addEagerConstraints(array $models)
    {
        $this->buildDictionary($this->models = Collection::make($models));
    }

    /**
     * Build a dictionary with the models.
	 * 构建字典用这些模型
     *
     * @param  \Illuminate\Database\Eloquent\Collection  $models
     * @return void
     */
    protected function buildDictionary(Collection $models)
    {
        foreach ($models as $model) {
            if ($model->{$this->morphType}) {
                $this->dictionary[$model->{$this->morphType}][$model->{$this->foreignKey}][] = $model;
            }
        }
    }

    /**
     * Get the results of the relationship.
	 * 得到关系的结果
     *
     * Called via eager load method of Eloquent query builder.
     *
     * @return mixed
     */
    public function getEager()
    {
        foreach (array_keys($this->dictionary) as $type) {
            $this->matchToMorphParents($type, $this->getResultsByType($type));
        }

        return $this->models;
    }

    /**
     * Get all of the relation results for a type.
	 * 得到一个类型的所有关系结果
     *
     * @param  string  $type
     * @return \Illuminate\Database\Eloquent\Collection
     */
    protected function getResultsByType($type)
    {
        $instance = $this->createModelByType($type);

        $ownerKey = $this->ownerKey ?? $instance->getKeyName();

        $query = $this->replayMacros($instance->newQuery())
                            ->mergeConstraintsFrom($this->getQuery())
                            ->with(array_merge(
                                $this->getQuery()->getEagerLoads(),
                                (array) ($this->morphableEagerLoads[get_class($instance)] ?? [])
                            ));

        $whereIn = $this->whereInMethod($instance, $ownerKey);

        return $query->{$whereIn}(
            $instance->getTable().'.'.$ownerKey, $this->gatherKeysByType($type)
        )->get();
    }

    /**
     * Gather all of the foreign keys for a given type.
	 * 收集给定类型的所有外键
     *
     * @param  string  $type
     * @return array
     */
    protected function gatherKeysByType($type)
    {
        return array_keys($this->dictionary[$type]);
    }

    /**
     * Create a new model instance by type.
	 * 创建一个新的模型实例按类型
     *
     * @param  string  $type
     * @return \Illuminate\Database\Eloquent\Model
     */
    public function createModelByType($type)
    {
        $class = Model::getActualClassNameForMorph($type);

        return tap(new $class, function ($instance) {
            if (! $instance->getConnectionName()) {
                $instance->setConnection($this->getConnection()->getName());
            }
        });
    }

    /**
     * Match the eagerly loaded results to their parents.
	 * 将急切加载的结果与他们的父母匹配
     *
     * @param  array  $models
     * @param  \Illuminate\Database\Eloquent\Collection  $results
     * @param  string  $relation
     * @return array
     */
    public function match(array $models, Collection $results, $relation)
    {
        return $models;
    }

    /**
     * Match the results for a given type to their parents.
	 * 将给定类型的结果与其父类型进行匹配
     *
     * @param  string  $type
     * @param  \Illuminate\Database\Eloquent\Collection  $results
     * @return void
     */
    protected function matchToMorphParents($type, Collection $results)
    {
        foreach ($results as $result) {
            $ownerKey = ! is_null($this->ownerKey) ? $result->{$this->ownerKey} : $result->getKey();

            if (isset($this->dictionary[$type][$ownerKey])) {
                foreach ($this->dictionary[$type][$ownerKey] as $model) {
                    $model->setRelation($this->relationName, $result);
                }
            }
        }
    }

    /**
     * Associate the model instance to the given parent.
	 * 关联模型实例到给定的父实例
     *
     * @param  \Illuminate\Database\Eloquent\Model  $model
     * @return \Illuminate\Database\Eloquent\Model
     */
    public function associate($model)
    {
        $this->parent->setAttribute(
            $this->foreignKey, $model instanceof Model ? $model->getKey() : null
        );

        $this->parent->setAttribute(
            $this->morphType, $model instanceof Model ? $model->getMorphClass() : null
        );

        return $this->parent->setRelation($this->relationName, $model);
    }

    /**
     * Dissociate previously associated model from the given parent.
	 * 将先前关联的模型与给定的父模型分离
     *
     * @return \Illuminate\Database\Eloquent\Model
     */
    public function dissociate()
    {
        $this->parent->setAttribute($this->foreignKey, null);

        $this->parent->setAttribute($this->morphType, null);

        return $this->parent->setRelation($this->relationName, null);
    }

    /**
     * Touch all of the related models for the relationship.
	 * 触摸关系的所有相关模型
     *
     * @return void
     */
    public function touch()
    {
        if (! is_null($this->child->{$this->foreignKey})) {
            parent::touch();
        }
    }

    /**
     * Make a new related instance for the given model.
	 * 创建一个新的相关实例为给定模型
     *
     * @param  \Illuminate\Database\Eloquent\Model  $parent
     * @return \Illuminate\Database\Eloquent\Model
     */
    protected function newRelatedInstanceFor(Model $parent)
    {
        return $parent->{$this->getRelationName()}()->getRelated()->newInstance();
    }

    /**
     * Get the foreign key "type" name.
	 * 得到外键"类型"名称
     *
     * @return string
     */
    public function getMorphType()
    {
        return $this->morphType;
    }

    /**
     * Get the dictionary used by the relationship.
	 * 得到关系使用的字典
     *
     * @return array
     */
    public function getDictionary()
    {
        return $this->dictionary;
    }

    /**
     * Specify which relations to load for a given morph type.
	 * 指定要为给定的变形类型加载哪些关系
     *
     * @param  array  $with
     * @return \Illuminate\Database\Eloquent\Relations\MorphTo
     */
    public function morphWith(array $with)
    {
        $this->morphableEagerLoads = array_merge(
            $this->morphableEagerLoads, $with
        );

        return $this;
    }

    /**
     * Replay stored macro calls on the actual related instance.
	 * 重播存储的宏调用在实际相关实例
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    protected function replayMacros(Builder $query)
    {
        foreach ($this->macroBuffer as $macro) {
            $query->{$macro['method']}(...$macro['parameters']);
        }

        return $query;
    }

    /**
     * Handle dynamic method calls to the relationship.
	 * 处理动态方法关系调用
     *
     * @param  string  $method
     * @param  array  $parameters
     * @return mixed
     */
    public function __call($method, $parameters)
    {
        try {
            $result = parent::__call($method, $parameters);

            if (in_array($method, ['select', 'selectRaw', 'selectSub', 'addSelect', 'withoutGlobalScopes'])) {
                $this->macroBuffer[] = compact('method', 'parameters');
            }

            return $result;
        }

        // If we tried to call a method that does not exist on the parent Builder instance,
        // we'll assume that we want to call a query macro (e.g. withTrashed) that only
        // exists on related models. We will just store the call and replay it later.
		// 如果我们试图调用父Builder实例上不存在的方法，
		// 我们将假设我们想调用仅存在于相关模型上的查询宏（例如withTrashed）。我们将只存储通话并稍后重播。
        catch (BadMethodCallException $e) {
            $this->macroBuffer[] = compact('method', 'parameters');

            return $this;
        }
    }
}

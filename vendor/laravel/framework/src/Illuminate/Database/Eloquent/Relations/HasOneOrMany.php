<?php
/**
 * 数据库，Eloquent有一个或多个
 */

namespace Illuminate\Database\Eloquent\Relations;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

abstract class HasOneOrMany extends Relation
{
    /**
     * The foreign key of the parent model.
	 * 父模型的外键
     *
     * @var string
     */
    protected $foreignKey;

    /**
     * The local key of the parent model.
	 * 父模型的本地键
     *
     * @var string
     */
    protected $localKey;

    /**
     * The count of self joins.
	 * 自连接的计数
     *
     * @var int
     */
    protected static $selfJoinCount = 0;

    /**
     * Create a new has one or many relationship instance.
	 * 创建新的有一个或多个关系实例
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  \Illuminate\Database\Eloquent\Model  $parent
     * @param  string  $foreignKey
     * @param  string  $localKey
     * @return void
     */
    public function __construct(Builder $query, Model $parent, $foreignKey, $localKey)
    {
        $this->localKey = $localKey;
        $this->foreignKey = $foreignKey;

        parent::__construct($query, $parent);
    }

    /**
     * Create and return an un-saved instance of the related model.
	 * 创建并返回相关模型的未保存实例
     *
     * @param  array  $attributes
     * @return \Illuminate\Database\Eloquent\Model
     */
    public function make(array $attributes = [])
    {
        return tap($this->related->newInstance($attributes), function ($instance) {
            $this->setForeignAttributesForCreate($instance);
        });
    }

    /**
     * Set the base constraints on the relation query.
	 * 在关系查询上设置基本约束
     *
     * @return void
     */
    public function addConstraints()
    {
        if (static::$constraints) {
            $this->query->where($this->foreignKey, '=', $this->getParentKey());

            $this->query->whereNotNull($this->foreignKey);
        }
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
        $whereIn = $this->whereInMethod($this->parent, $this->localKey);

        $this->query->{$whereIn}(
            $this->foreignKey, $this->getKeys($models, $this->localKey)
        );
    }

    /**
     * Match the eagerly loaded results to their single parents.
	 * 将急切加载的结果与他们的单亲父母相匹配
     *
     * @param  array  $models
     * @param  \Illuminate\Database\Eloquent\Collection  $results
     * @param  string  $relation
     * @return array
     */
    public function matchOne(array $models, Collection $results, $relation)
    {
        return $this->matchOneOrMany($models, $results, $relation, 'one');
    }

    /**
     * Match the eagerly loaded results to their many parents.
	 * 将急切加载的结果与他们的许多父母相匹配
     *
     * @param  array  $models
     * @param  \Illuminate\Database\Eloquent\Collection  $results
     * @param  string  $relation
     * @return array
     */
    public function matchMany(array $models, Collection $results, $relation)
    {
        return $this->matchOneOrMany($models, $results, $relation, 'many');
    }

    /**
     * Match the eagerly loaded results to their many parents.
	 * 将急切加载的结果与他们的许多父母相匹配
     *
     * @param  array  $models
     * @param  \Illuminate\Database\Eloquent\Collection  $results
     * @param  string  $relation
     * @param  string  $type
     * @return array
     */
    protected function matchOneOrMany(array $models, Collection $results, $relation, $type)
    {
        $dictionary = $this->buildDictionary($results);

        // Once we have the dictionary we can simply spin through the parent models to
        // link them up with their children using the keyed dictionary to make the
        // matching very convenient and easy work. Then we'll just return them.
		// 一旦我们有了字典，我们就可以简单地旋转父模型，使用键控字典将它们与它们的孩子联系起来，
		// 使匹配变得非常方便和容易。然后我们只需返回它们。
        foreach ($models as $model) {
            if (isset($dictionary[$key = $model->getAttribute($this->localKey)])) {
                $model->setRelation(
                    $relation, $this->getRelationValue($dictionary, $key, $type)
                );
            }
        }

        return $models;
    }

    /**
     * Get the value of a relationship by one or many type.
	 * 得到关系的值通过一个或多个类型
     *
     * @param  array  $dictionary
     * @param  string  $key
     * @param  string  $type
     * @return mixed
     */
    protected function getRelationValue(array $dictionary, $key, $type)
    {
        $value = $dictionary[$key];

        return $type === 'one' ? reset($value) : $this->related->newCollection($value);
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
        $foreign = $this->getForeignKeyName();

        return $results->mapToDictionary(function ($result) use ($foreign) {
            return [$result->{$foreign} => $result];
        })->all();
    }

    /**
     * Find a model by its primary key or return new instance of the related model.
	 * 查找模型通过主键或返回相关模型的新实例
     *
     * @param  mixed  $id
     * @param  array  $columns
     * @return \Illuminate\Support\Collection|\Illuminate\Database\Eloquent\Model
     */
    public function findOrNew($id, $columns = ['*'])
    {
        if (is_null($instance = $this->find($id, $columns))) {
            $instance = $this->related->newInstance();

            $this->setForeignAttributesForCreate($instance);
        }

        return $instance;
    }

    /**
     * Get the first related model record matching the attributes or instantiate it.
	 * 得到与属性匹配的第一个相关模型记录，或者实例化它。
     *
     * @param  array  $attributes
     * @param  array  $values
     * @return \Illuminate\Database\Eloquent\Model
     */
    public function firstOrNew(array $attributes, array $values = [])
    {
        if (is_null($instance = $this->where($attributes)->first())) {
            $instance = $this->related->newInstance($attributes + $values);

            $this->setForeignAttributesForCreate($instance);
        }

        return $instance;
    }

    /**
     * Get the first related record matching the attributes or create it.
	 * 得到匹配属性的第一个相关记录，或者创建它。
     *
     * @param  array  $attributes
     * @param  array  $values
     * @return \Illuminate\Database\Eloquent\Model
     */
    public function firstOrCreate(array $attributes, array $values = [])
    {
        if (is_null($instance = $this->where($attributes)->first())) {
            $instance = $this->create($attributes + $values);
        }

        return $instance;
    }

    /**
     * Create or update a related record matching the attributes, and fill it with values.
	 * 创建或更新与属性匹配的相关记录，并用值填充它。
     *
     * @param  array  $attributes
     * @param  array  $values
     * @return \Illuminate\Database\Eloquent\Model
     */
    public function updateOrCreate(array $attributes, array $values = [])
    {
        return tap($this->firstOrNew($attributes), function ($instance) use ($values) {
            $instance->fill($values);

            $instance->save();
        });
    }

    /**
     * Attach a model instance to the parent model.
	 * 附加模型实例到父模型上
     *
     * @param  \Illuminate\Database\Eloquent\Model  $model
     * @return \Illuminate\Database\Eloquent\Model|false
     */
    public function save(Model $model)
    {
        $this->setForeignAttributesForCreate($model);

        return $model->save() ? $model : false;
    }

    /**
     * Attach a collection of models to the parent instance.
	 * 附加模型集合到父实例上
     *
     * @param  iterable  $models
     * @return iterable
     */
    public function saveMany($models)
    {
        foreach ($models as $model) {
            $this->save($model);
        }

        return $models;
    }

    /**
     * Create a new instance of the related model.
	 * 创建相关模型的新实例
     *
     * @param  array  $attributes
     * @return \Illuminate\Database\Eloquent\Model
     */
    public function create(array $attributes = [])
    {
        return tap($this->related->newInstance($attributes), function ($instance) {
            $this->setForeignAttributesForCreate($instance);

            $instance->save();
        });
    }

    /**
     * Create a Collection of new instances of the related model.
	 * 创建相关模型的新实例集合
     *
     * @param  iterable  $records
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function createMany(iterable $records)
    {
        $instances = $this->related->newCollection();

        foreach ($records as $record) {
            $instances->push($this->create($record));
        }

        return $instances;
    }

    /**
     * Set the foreign ID for creating a related model.
	 * 设置创建相关模型的外部ID
     *
     * @param  \Illuminate\Database\Eloquent\Model  $model
     * @return void
     */
    protected function setForeignAttributesForCreate(Model $model)
    {
        $model->setAttribute($this->getForeignKeyName(), $this->getParentKey());
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
        if ($query->getQuery()->from == $parentQuery->getQuery()->from) {
            return $this->getRelationExistenceQueryForSelfRelation($query, $parentQuery, $columns);
        }

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
    public function getRelationExistenceQueryForSelfRelation(Builder $query, Builder $parentQuery, $columns = ['*'])
    {
        $query->from($query->getModel()->getTable().' as '.$hash = $this->getRelationCountHash());

        $query->getModel()->setTable($hash);

        return $query->select($columns)->whereColumn(
            $this->getQualifiedParentKeyName(), '=', $hash.'.'.$this->getForeignKeyName()
        );
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
     * Get the key for comparing against the parent key in "has" query.
	 * 得到用于与"has"查询中的父键进行比较的键。
     *
     * @return string
     */
    public function getExistenceCompareKey()
    {
        return $this->getQualifiedForeignKeyName();
    }

    /**
     * Get the key value of the parent's local key.
	 * 得到父节点本地键的键值
     *
     * @return mixed
     */
    public function getParentKey()
    {
        return $this->parent->getAttribute($this->localKey);
    }

    /**
     * Get the fully qualified parent key name.
	 * 得到完全限定父键名
     *
     * @return string
     */
    public function getQualifiedParentKeyName()
    {
        return $this->parent->qualifyColumn($this->localKey);
    }

    /**
     * Get the plain foreign key.
	 * 得到普通外键
     *
     * @return string
     */
    public function getForeignKeyName()
    {
        $segments = explode('.', $this->getQualifiedForeignKeyName());

        return end($segments);
    }

    /**
     * Get the foreign key for the relationship.
	 * 得到关系的外键
     *
     * @return string
     */
    public function getQualifiedForeignKeyName()
    {
        return $this->foreignKey;
    }

    /**
     * Get the local key for the relationship.
	 * 得到关系的本地键
     *
     * @return string
     */
    public function getLocalKeyName()
    {
        return $this->localKey;
    }
}

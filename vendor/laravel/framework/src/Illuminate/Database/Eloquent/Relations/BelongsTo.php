<?php
/**
 * 数据库，Eloquent从属于
 */

namespace Illuminate\Database\Eloquent\Relations;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Concerns\SupportsDefaultModels;

class BelongsTo extends Relation
{
    use SupportsDefaultModels;

    /**
     * The child model instance of the relation.
	 * 子模型实例的关系
     */
    protected $child;

    /**
     * The foreign key of the parent model.
	 * 父模型的外键
     *
     * @var string
     */
    protected $foreignKey;

    /**
     * The associated key on the parent model.
	 * 父模型上的关联键
     *
     * @var string
     */
    protected $ownerKey;

    /**
     * The name of the relationship.
	 * 关系的名称
     *
     * @var string
     */
    protected $relationName;

    /**
     * The count of self joins.
	 * 自连接的计数
     *
     * @var int
     */
    protected static $selfJoinCount = 0;

    /**
     * Create a new belongs to relationship instance.
	 * 创建新的归属关系实例
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  \Illuminate\Database\Eloquent\Model  $child
     * @param  string  $foreignKey
     * @param  string  $ownerKey
     * @param  string  $relationName
     * @return void
     */
    public function __construct(Builder $query, Model $child, $foreignKey, $ownerKey, $relationName)
    {
        $this->ownerKey = $ownerKey;
        $this->relationName = $relationName;
        $this->foreignKey = $foreignKey;

        // In the underlying base relationship class, this variable is referred to as
        // the "parent" since most relationships are not inversed. But, since this
        // one is we will create a "child" variable for much better readability.
		// 在基础基础关系类中，这个变量被称为"父"，因为大多数关系都没有反转。
		// 但是，既然是这样，我们将创建一个"子"变量，以提高可读性。
        $this->child = $child;

        parent::__construct($query, $child);
    }

    /**
     * Get the results of the relationship.
	 * 得到关系的结果
     *
     * @return mixed
     */
    public function getResults()
    {
        if (is_null($this->child->{$this->foreignKey})) {
            return $this->getDefaultFor($this->parent);
        }

        return $this->query->first() ?: $this->getDefaultFor($this->parent);
    }

    /**
     * Set the base constraints on the relation query.
	 * 设置基本约束的关系结果
     *
     * @return void
     */
    public function addConstraints()
    {
        if (static::$constraints) {
            // For belongs to relationships, which are essentially the inverse of has one
            // or has many relationships, we need to actually query on the primary key
            // of the related models matching on the foreign key that's on a parent.
			// 对于本质上与具有一个或多个关系相反的属于关系，
			// 我们需要实际查询相关模型的主键，以匹配父节点上的外键。
            $table = $this->related->getTable();

            $this->query->where($table.'.'.$this->ownerKey, '=', $this->child->{$this->foreignKey});
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
        // We'll grab the primary key name of the related models since it could be set to
        // a non-standard name and not "id". We will then construct the constraint for
        // our eagerly loading query so it returns the proper models from execution.
		// 我们将获取相关模型的主键名称，因为它可以设置为非标准名称，而不是"id"。
		// 然后，我们将为急切加载的查询构建约束，以便它从执行中返回正确的模型。
        $key = $this->related->getTable().'.'.$this->ownerKey;

        $whereIn = $this->whereInMethod($this->related, $this->ownerKey);

        $this->query->{$whereIn}($key, $this->getEagerModelKeys($models));
    }

    /**
     * Gather the keys from an array of related models.
	 * 收集键从相关模型的数组中
     *
     * @param  array  $models
     * @return array
     */
    protected function getEagerModelKeys(array $models)
    {
        $keys = [];

        // First we need to gather all of the keys from the parent models so we know what
        // to query for via the eager loading query. We will add them to an array then
        // execute a "where in" statement to gather up all of those related records.
		// 首先，我们需要从父模型中收集所有键，以便我们知道通过急切加载查询查询要查询什么。
		// 我们将它们添加到数组中，然后执行"where in"语句来收集所有相关记录。
        foreach ($models as $model) {
            if (! is_null($value = $model->{$this->foreignKey})) {
                $keys[] = $value;
            }
        }

        sort($keys);

        return array_values(array_unique($keys));
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
            $model->setRelation($relation, $this->getDefaultFor($model));
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
        $foreign = $this->foreignKey;

        $owner = $this->ownerKey;

        // First we will get to build a dictionary of the child models by their primary
        // key of the relationship, then we can easily match the children back onto
        // the parents using that dictionary and the primary key of the children.
		// 首先，我们将根据关系的主键构建一个子模型字典，
		// 然后我们可以使用该字典和子模型的主键轻松地将子模型匹配回父母。
        $dictionary = [];

        foreach ($results as $result) {
            $dictionary[$result->getAttribute($owner)] = $result;
        }

        // Once we have the dictionary constructed, we can loop through all the parents
        // and match back onto their children using these keys of the dictionary and
        // the primary key of the children to map them onto the correct instances.
		// 一旦我们构建了字典，我们就可以遍历所有父节点，
		// 并使用字典的这些键和子节点的主键将它们映射到正确的实例上，从而匹配回它们的子节点。
        foreach ($models as $model) {
            if (isset($dictionary[$model->{$foreign}])) {
                $model->setRelation($relation, $dictionary[$model->{$foreign}]);
            }
        }

        return $models;
    }

    /**
     * Associate the model instance to the given parent.
	 * 关联模型实例到给定的父实例
     *
     * @param  \Illuminate\Database\Eloquent\Model|int|string  $model
     * @return \Illuminate\Database\Eloquent\Model
     */
    public function associate($model)
    {
        $ownerKey = $model instanceof Model ? $model->getAttribute($this->ownerKey) : $model;

        $this->child->setAttribute($this->foreignKey, $ownerKey);

        if ($model instanceof Model) {
            $this->child->setRelation($this->relationName, $model);
        } elseif ($this->child->isDirty($this->foreignKey)) {
            $this->child->unsetRelation($this->relationName);
        }

        return $this->child;
    }

    /**
     * Dissociate previously associated model from the given parent.
	 * 将先前关联的模型与给定的父模型分离
     *
     * @return \Illuminate\Database\Eloquent\Model
     */
    public function dissociate()
    {
        $this->child->setAttribute($this->foreignKey, null);

        return $this->child->setRelation($this->relationName, null);
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
            return $this->getRelationExistenceQueryForSelfRelation($query, $parentQuery, $columns);
        }

        return $query->select($columns)->whereColumn(
            $this->getQualifiedForeignKeyName(), '=', $query->qualifyColumn($this->ownerKey)
        );
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
        $query->select($columns)->from(
            $query->getModel()->getTable().' as '.$hash = $this->getRelationCountHash()
        );

        $query->getModel()->setTable($hash);

        return $query->whereColumn(
            $hash.'.'.$this->ownerKey, '=', $this->getQualifiedForeignKeyName()
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
     * Determine if the related model has an auto-incrementing ID.
	 * 确定相关模型是否具有自动递增的ID
     *
     * @return bool
     */
    protected function relationHasIncrementingId()
    {
        return $this->related->getIncrementing() &&
                                $this->related->getKeyType() === 'int';
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
        return $this->related->newInstance();
    }

    /**
     * Get the child of the relationship.
	 * 得到这段关系的子
     *
     * @return \Illuminate\Database\Eloquent\Model
     */
    public function getChild()
    {
        return $this->child;
    }

    /**
     * Get the foreign key of the relationship.
	 * 得到关系的外键
     *
     * @return string
     */
    public function getForeignKeyName()
    {
        return $this->foreignKey;
    }

    /**
     * Get the fully qualified foreign key of the relationship.
	 * 得到关系的完全限定外键
     *
     * @return string
     */
    public function getQualifiedForeignKeyName()
    {
        return $this->child->qualifyColumn($this->foreignKey);
    }

    /**
     * Get the associated key of the relationship.
	 * 得到关系的关联键
     *
     * @return string
     */
    public function getOwnerKeyName()
    {
        return $this->ownerKey;
    }

    /**
     * Get the fully qualified associated key of the relationship.
	 * 得到关系的完全限定关联键
     *
     * @return string
     */
    public function getQualifiedOwnerKeyName()
    {
        return $this->related->qualifyColumn($this->ownerKey);
    }

    /**
     * Get the name of the relationship.
	 * 得到关系名称
     *
     * @return string
     */
    public function getRelationName()
    {
        return $this->relationName;
    }
}

<?php
/**
 * 数据库，Eloquent向许多变形
 */

namespace Illuminate\Database\Eloquent\Relations;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;

class MorphToMany extends BelongsToMany
{
    /**
     * The type of the polymorphic relation.
	 * 多态关系类型
     *
     * @var string
     */
    protected $morphType;

    /**
     * The class name of the morph type constraint.
	 * 变形类型约束类名
     *
     * @var string
     */
    protected $morphClass;

    /**
     * Indicates if we are connecting the inverse of the relation.
	 * 指明是否我们连接相反关系
     *
     * This primarily affects the morphClass constraint.
     *
     * @var bool
     */
    protected $inverse;

    /**
     * Create a new morph to many relationship instance.
	 * 创建新的变形多个关系实例
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  \Illuminate\Database\Eloquent\Model  $parent
     * @param  string  $name
     * @param  string  $table
     * @param  string  $foreignPivotKey
     * @param  string  $relatedPivotKey
     * @param  string  $parentKey
     * @param  string  $relatedKey
     * @param  string|null  $relationName
     * @param  bool  $inverse
     * @return void
     */
    public function __construct(Builder $query, Model $parent, $name, $table, $foreignPivotKey,
                                $relatedPivotKey, $parentKey, $relatedKey, $relationName = null, $inverse = false)
    {
        $this->inverse = $inverse;
        $this->morphType = $name.'_type';
        $this->morphClass = $inverse ? $query->getModel()->getMorphClass() : $parent->getMorphClass();

        parent::__construct(
            $query, $parent, $table, $foreignPivotKey,
            $relatedPivotKey, $parentKey, $relatedKey, $relationName
        );
    }

    /**
     * Set the where clause for the relation query.
	 * 设置where子句为关联查询
     *
     * @return $this
     */
    protected function addWhereConstraints()
    {
        parent::addWhereConstraints();

        $this->query->where($this->table.'.'.$this->morphType, $this->morphClass);

        return $this;
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
        parent::addEagerConstraints($models);

        $this->query->where($this->table.'.'.$this->morphType, $this->morphClass);
    }

    /**
     * Create a new pivot attachment record.
	 * 创建一个新的轴心附件记录
     *
     * @param  int  $id
     * @param  bool  $timed
     * @return array
     */
    protected function baseAttachRecord($id, $timed)
    {
        return Arr::add(
            parent::baseAttachRecord($id, $timed), $this->morphType, $this->morphClass
        );
    }

    /**
     * Add the constraints for a relationship count query.
	 * 为关系计数查询添加约束
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  \Illuminate\Database\Eloquent\Builder  $parentQuery
     * @param  array|mixed  $columns
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function getRelationExistenceQuery(Builder $query, Builder $parentQuery, $columns = ['*'])
    {
        return parent::getRelationExistenceQuery($query, $parentQuery, $columns)->where(
            $this->table.'.'.$this->morphType, $this->morphClass
        );
    }

    /**
     * Get the pivot models that are currently attached.
	 * 得到当前附加的轴心模型
     *
     * @return \Illuminate\Support\Collection
     */
    protected function getCurrentlyAttachedPivots()
    {
        return parent::getCurrentlyAttachedPivots()->map(function ($record) {
            return $record instanceof MorphPivot
                            ? $record->setMorphType($this->morphType)
                                     ->setMorphClass($this->morphClass)
                            : $record;
        });
    }

    /**
     * Create a new query builder for the pivot table.
	 * 为数据透视表创建一个新的查询生成器
     *
     * @return \Illuminate\Database\Query\Builder
     */
    public function newPivotQuery()
    {
        return parent::newPivotQuery()->where($this->morphType, $this->morphClass);
    }

    /**
     * Create a new pivot model instance.
	 * 创建一个新的轴心模型实例
     *
     * @param  array  $attributes
     * @param  bool  $exists
     * @return \Illuminate\Database\Eloquent\Relations\Pivot
     */
    public function newPivot(array $attributes = [], $exists = false)
    {
        $using = $this->using;

        $pivot = $using ? $using::fromRawAttributes($this->parent, $attributes, $this->table, $exists)
                        : MorphPivot::fromAttributes($this->parent, $attributes, $this->table, $exists);

        $pivot->setPivotKeys($this->foreignPivotKey, $this->relatedPivotKey)
              ->setMorphType($this->morphType)
              ->setMorphClass($this->morphClass);

        return $pivot;
    }

    /**
     * Get the pivot columns for the relation.
	 * 得到得到关系的主列
     *
     * "pivot_" is prefixed at each column for easy removal later.
     *
     * @return array
     */
    protected function aliasedPivotColumns()
    {
        $defaults = [$this->foreignPivotKey, $this->relatedPivotKey, $this->morphType];

        return collect(array_merge($defaults, $this->pivotColumns))->map(function ($column) {
            return $this->table.'.'.$column.' as pivot_'.$column;
        })->unique()->all();
    }

    /**
     * Get the foreign key "type" name.
	 * 得到外链"类型"名
     *
     * @return string
     */
    public function getMorphType()
    {
        return $this->morphType;
    }

    /**
     * Get the class name of the parent model.
	 * 得到父模型的类名
     *
     * @return string
     */
    public function getMorphClass()
    {
        return $this->morphClass;
    }

    /**
     * Get the indicator for a reverse relationship.
	 * 得到反向关系的指示符
     *
     * @return bool
     */
    public function getInverse()
    {
        return $this->inverse;
    }
}

<?php
/**
 * 数据库，Eloquent为轴
 */

namespace Illuminate\Database\Eloquent\Relations\Concerns;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

trait AsPivot
{
    /**
     * The parent model of the relationship.
	 * 父模型关系
     *
     * @var \Illuminate\Database\Eloquent\Model
     */
    public $pivotParent;

    /**
     * The name of the foreign key column.
	 * 外键列名称
     *
     * @var string
     */
    protected $foreignKey;

    /**
     * The name of the "other key" column.
	 * "其他键"列名
     *
     * @var string
     */
    protected $relatedKey;

    /**
     * Create a new pivot model instance.
	 * 创建一个新的支点模型实例
     *
     * @param  \Illuminate\Database\Eloquent\Model  $parent
     * @param  array  $attributes
     * @param  string  $table
     * @param  bool  $exists
     * @return static
     */
    public static function fromAttributes(Model $parent, $attributes, $table, $exists = false)
    {
        $instance = new static;

        $instance->timestamps = $instance->hasTimestampAttributes($attributes);

        // The pivot model is a "dynamic" model since we will set the tables dynamically
        // for the instance. This allows it work for any intermediate tables for the
        // many to many relationship that are defined by this developer's classes.
		// 数据透视模型是一个"动态"模型，因为我们将动态地设置表为实例。
		// 这允许它任何中间表都可以使用由开发人员的类定义的多对多关系。
        $instance->setConnection($parent->getConnectionName())
            ->setTable($table)
            ->forceFill($attributes)
            ->syncOriginal();

        // We store off the parent instance so we will access the timestamp column names
        // for the model, since the pivot model timestamps aren't easily configurable
        // from the developer's point of view. We can use the parents to get these.
		// 我们存储父实例，以便访问模型的时间戳列名，
		// 由于pivot模型时间戳不容易从开发者的角度来看。我们可以利用父类来得到这些。
        $instance->pivotParent = $parent;

        $instance->exists = $exists;

        return $instance;
    }

    /**
     * Create a new pivot model from raw values returned from a query.
	 * 创建新的数据透视模型根据查询返回的原始值
     *
     * @param  \Illuminate\Database\Eloquent\Model  $parent
     * @param  array  $attributes
     * @param  string  $table
     * @param  bool  $exists
     * @return static
     */
    public static function fromRawAttributes(Model $parent, $attributes, $table, $exists = false)
    {
        $instance = static::fromAttributes($parent, [], $table, $exists);

        $instance->timestamps = $instance->hasTimestampAttributes($attributes);

        $instance->setRawAttributes($attributes, $exists);

        return $instance;
    }

    /**
     * Set the keys for a save update query.
	 * 设置主键为保存更新查询
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    protected function setKeysForSaveQuery(Builder $query)
    {
        if (isset($this->attributes[$this->getKeyName()])) {
            return parent::setKeysForSaveQuery($query);
        }

        $query->where($this->foreignKey, $this->getOriginal(
            $this->foreignKey, $this->getAttribute($this->foreignKey)
        ));

        return $query->where($this->relatedKey, $this->getOriginal(
            $this->relatedKey, $this->getAttribute($this->relatedKey)
        ));
    }

    /**
     * Delete the pivot model record from the database.
	 * 删除数据透视模型记录从数据库中
     *
     * @return int
     */
    public function delete()
    {
        if (isset($this->attributes[$this->getKeyName()])) {
            return (int) parent::delete();
        }

        if ($this->fireModelEvent('deleting') === false) {
            return 0;
        }

        $this->touchOwners();

        return tap($this->getDeleteQuery()->delete(), function () {
            $this->fireModelEvent('deleted', false);
        });
    }

    /**
     * Get the query builder for a delete operation on the pivot.
	 * 得到对数据透视进行删除操作的查询构建器
     *
     * @return \Illuminate\Database\Eloquent\Builder
     */
    protected function getDeleteQuery()
    {
        return $this->newQueryWithoutRelationships()->where([
            $this->foreignKey => $this->getOriginal($this->foreignKey, $this->getAttribute($this->foreignKey)),
            $this->relatedKey => $this->getOriginal($this->relatedKey, $this->getAttribute($this->relatedKey)),
        ]);
    }

    /**
     * Get the table associated with the model.
	 * 得到与模型关联的表
     *
     * @return string
     */
    public function getTable()
    {
        if (! isset($this->table)) {
            $this->setTable(str_replace(
                '\\', '', Str::snake(Str::singular(class_basename($this)))
            ));
        }

        return $this->table;
    }

    /**
     * Get the foreign key column name.
	 * 得到外键列名
     *
     * @return string
     */
    public function getForeignKey()
    {
        return $this->foreignKey;
    }

    /**
     * Get the "related key" column name.
	 * 得到"相关键"列名
     *
     * @return string
     */
    public function getRelatedKey()
    {
        return $this->relatedKey;
    }

    /**
     * Get the "related key" column name.
	 * 得到"相关键"列名
     *
     * @return string
     */
    public function getOtherKey()
    {
        return $this->getRelatedKey();
    }

    /**
     * Set the key names for the pivot model instance.
	 * 设置pivot模型实例的键名
     *
     * @param  string  $foreignKey
     * @param  string  $relatedKey
     * @return $this
     */
    public function setPivotKeys($foreignKey, $relatedKey)
    {
        $this->foreignKey = $foreignKey;

        $this->relatedKey = $relatedKey;

        return $this;
    }

    /**
     * Determine if the pivot model or given attributes has timestamp attributes.
	 * 确定数据透视模型或给定属性是否具有时间戳属性
     *
     * @param  array|null  $attributes
     * @return bool
     */
    public function hasTimestampAttributes($attributes = null)
    {
        return array_key_exists($this->getCreatedAtColumn(), $attributes ?? $this->attributes);
    }

    /**
     * Get the name of the "created at" column.
	 * 得到"创建时间"列的名称
     *
     * @return string
     */
    public function getCreatedAtColumn()
    {
        return $this->pivotParent
            ? $this->pivotParent->getCreatedAtColumn()
            : parent::getCreatedAtColumn();
    }

    /**
     * Get the name of the "updated at" column.
	 * 得到"更新时间"列的名称
     *
     * @return string
     */
    public function getUpdatedAtColumn()
    {
        return $this->pivotParent
            ? $this->pivotParent->getUpdatedAtColumn()
            : parent::getUpdatedAtColumn();
    }

    /**
     * Get the queueable identity for the entity.
	 * 得到实体的可排队标识
     *
     * @return mixed
     */
    public function getQueueableId()
    {
        if (isset($this->attributes[$this->getKeyName()])) {
            return $this->getKey();
        }

        return sprintf(
            '%s:%s:%s:%s',
            $this->foreignKey, $this->getAttribute($this->foreignKey),
            $this->relatedKey, $this->getAttribute($this->relatedKey)
        );
    }

    /**
     * Get a new query to restore one or more models by their queueable IDs.
	 * 得到一个新查询，根据可排队id还原一个或多个模型。
     *
     * @param  int[]|string[]|string  $ids
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function newQueryForRestoration($ids)
    {
        if (is_array($ids)) {
            return $this->newQueryForCollectionRestoration($ids);
        }

        if (! Str::contains($ids, ':')) {
            return parent::newQueryForRestoration($ids);
        }

        $segments = explode(':', $ids);

        return $this->newQueryWithoutScopes()
            ->where($segments[0], $segments[1])
            ->where($segments[2], $segments[3]);
    }

    /**
     * Get a new query to restore multiple models by their queueable IDs.
	 * 得到一个新查询，根据可排队id恢复多个模型。
     *
     * @param  int[]|string[]  $ids
     * @return \Illuminate\Database\Eloquent\Builder
     */
    protected function newQueryForCollectionRestoration(array $ids)
    {
        $ids = array_values($ids);

        if (! Str::contains($ids[0], ':')) {
            return parent::newQueryForRestoration($ids);
        }

        $query = $this->newQueryWithoutScopes();

        foreach ($ids as $id) {
            $segments = explode(':', $id);

            $query->orWhere(function ($query) use ($segments) {
                return $query->where($segments[0], $segments[1])
                    ->where($segments[2], $segments[3]);
            });
        }

        return $query;
    }

    /**
     * Unset all the loaded relations for the instance.
	 * 取消为实例加载的所有关系的设置
     *
     * @return $this
     */
    public function unsetRelations()
    {
        $this->pivotParent = null;
        $this->relations = [];

        return $this;
    }
}

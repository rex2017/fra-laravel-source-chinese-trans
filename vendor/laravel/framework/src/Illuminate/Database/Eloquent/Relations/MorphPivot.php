<?php
/**
 * 数据库，Eloquent改变主
 */

namespace Illuminate\Database\Eloquent\Relations;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;

class MorphPivot extends Pivot
{
    /**
     * The type of the polymorphic relation.
	 * 多态关系的类型
     *
     * Explicitly define this so it's not included in saved attributes.
     *
     * @var string
     */
    protected $morphType;

    /**
     * The value of the polymorphic relation.
	 * 多态关系值 
     *
     * Explicitly define this so it's not included in saved attributes.
     *
     * @var string
     */
    protected $morphClass;

    /**
     * Set the keys for a save update query.
	 * 为查询更新保存键
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    protected function setKeysForSaveQuery(Builder $query)
    {
        $query->where($this->morphType, $this->morphClass);

        return parent::setKeysForSaveQuery($query);
    }

    /**
     * Delete the pivot model record from the database.
	 * 删除数据透视模型记录
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

        $query = $this->getDeleteQuery();

        $query->where($this->morphType, $this->morphClass);

        return tap($query->delete(), function () {
            $this->fireModelEvent('deleted', false);
        });
    }

    /**
     * Set the morph type for the pivot.
	 * 设置主的变形类型
     *
     * @param  string  $morphType
     * @return $this
     */
    public function setMorphType($morphType)
    {
        $this->morphType = $morphType;

        return $this;
    }

    /**
     * Set the morph class for the pivot.
	 * 设置变形类为轴
     *
     * @param  string  $morphClass
     * @return \Illuminate\Database\Eloquent\Relations\MorphPivot
     */
    public function setMorphClass($morphClass)
    {
        $this->morphClass = $morphClass;

        return $this;
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
            '%s:%s:%s:%s:%s:%s',
            $this->foreignKey, $this->getAttribute($this->foreignKey),
            $this->relatedKey, $this->getAttribute($this->relatedKey),
            $this->morphType, $this->morphClass
        );
    }

    /**
     * Get a new query to restore one or more models by their queueable IDs.
	 * 得到一个新查询，根据可排队id还原一个或多个模型。
     *
     * @param  array|int  $ids
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
                        ->where($segments[2], $segments[3])
                        ->where($segments[4], $segments[5]);
    }

    /**
     * Get a new query to restore multiple models by their queueable IDs.
	 * 得到一个新查询，根据可排队id恢复多个模型
     *
     * @param  array  $ids
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
                             ->where($segments[2], $segments[3])
                             ->where($segments[4], $segments[5]);
            });
        }

        return $query;
    }
}

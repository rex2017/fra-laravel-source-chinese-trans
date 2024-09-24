<?php
/**
 * 数据库，Eloquent改变很多
 */

namespace Illuminate\Database\Eloquent\Relations;

use Illuminate\Database\Eloquent\Collection;

class MorphMany extends MorphOneOrMany
{
    /**
     * Get the results of the relationship.
	 * 得到关系的结果
     *
     * @return mixed
     */
    public function getResults()
    {
        return ! is_null($this->getParentKey())
                ? $this->query->get()
                : $this->related->newCollection();
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
	 * 将急切加载的结果与他们的父母匹配
     *
     * @param  array  $models
     * @param  \Illuminate\Database\Eloquent\Collection  $results
     * @param  string  $relation
     * @return array
     */
    public function match(array $models, Collection $results, $relation)
    {
        return $this->matchMany($models, $results, $relation);
    }
}

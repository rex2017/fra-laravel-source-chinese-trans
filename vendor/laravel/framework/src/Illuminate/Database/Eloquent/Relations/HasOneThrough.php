<?php
/**
 * 数据库，Eloquent有一个通过
 */

namespace Illuminate\Database\Eloquent\Relations;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Concerns\SupportsDefaultModels;

class HasOneThrough extends HasManyThrough
{
    use SupportsDefaultModels;

    /**
     * Get the results of the relationship.
	 * 得到关系的结果
     *
     * @return mixed
     */
    public function getResults()
    {
        return $this->first() ?: $this->getDefaultFor($this->farParent);
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
	 * 将急切加载的结果与他们的父母匹配
     *
     * @param  array  $models
     * @param  \Illuminate\Database\Eloquent\Collection  $results
     * @param  string  $relation
     * @return array
     */
    public function match(array $models, Collection $results, $relation)
    {
        $dictionary = $this->buildDictionary($results);

        // Once we have the dictionary we can simply spin through the parent models to
        // link them up with their children using the keyed dictionary to make the
        // matching very convenient and easy work. Then we'll just return them.
		// 一旦我们有了字典，我们就可以简单地旋转父模型，使用键控字典将它们与它们的孩子联系起来，
		// 使匹配变得非常方便和容易。然后我们只需返回它们。
        foreach ($models as $model) {
            if (isset($dictionary[$key = $model->getAttribute($this->localKey)])) {
                $value = $dictionary[$key];
                $model->setRelation(
                    $relation, reset($value)
                );
            }
        }

        return $models;
    }

    /**
     * Make a new related instance for the given model.
	 * 为给定模型创建一个新的相关实例
     *
     * @param  \Illuminate\Database\Eloquent\Model  $parent
     * @return \Illuminate\Database\Eloquent\Model
     */
    public function newRelatedInstanceFor(Model $parent)
    {
        return $this->related->newInstance();
    }
}

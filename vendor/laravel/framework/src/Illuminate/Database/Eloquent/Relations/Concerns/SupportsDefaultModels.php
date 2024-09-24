<?php
/**
 * 数据库，Eloquent支持默认模型
 */

namespace Illuminate\Database\Eloquent\Relations\Concerns;

use Illuminate\Database\Eloquent\Model;

trait SupportsDefaultModels
{
    /**
     * Indicates if a default model instance should be used.
	 * 指明是否应使用默认模型实例
     *
     * Alternatively, may be a Closure or array.
     *
     * @var \Closure|array|bool
     */
    protected $withDefault;

    /**
     * Make a new related instance for the given model.
	 * 创建一个新的相关实例为给定模型
     *
     * @param  \Illuminate\Database\Eloquent\Model  $parent
     * @return \Illuminate\Database\Eloquent\Model
     */
    abstract protected function newRelatedInstanceFor(Model $parent);

    /**
     * Return a new model instance in case the relationship does not exist.
	 * 返回一个新的模型实例，如果关系不存在。
     *
     * @param  \Closure|array|bool  $callback
     * @return $this
     */
    public function withDefault($callback = true)
    {
        $this->withDefault = $callback;

        return $this;
    }

    /**
     * Get the default value for this relation.
	 * 得到默认值关联
     *
     * @param  \Illuminate\Database\Eloquent\Model  $parent
     * @return \Illuminate\Database\Eloquent\Model|null
     */
    protected function getDefaultFor(Model $parent)
    {
        if (! $this->withDefault) {
            return;
        }

        $instance = $this->newRelatedInstanceFor($parent);

        if (is_callable($this->withDefault)) {
            return call_user_func($this->withDefault, $instance, $parent) ?: $instance;
        }

        if (is_array($this->withDefault)) {
            $instance->forceFill($this->withDefault);
        }

        return $instance;
    }
}

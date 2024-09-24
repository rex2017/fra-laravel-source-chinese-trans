<?php
/**
 * 数据库，Eloquent关联未找到异常
 */

namespace Illuminate\Database\Eloquent;

use RuntimeException;

class RelationNotFoundException extends RuntimeException
{
    /**
     * The name of the affected Eloquent model.
	 * 受影响的Eloquent模型名称
     *
     * @var string
     */
    public $model;

    /**
     * The name of the relation.
	 * 关联名称
     *
     * @var string
     */
    public $relation;

    /**
     * Create a new exception instance.
	 * 创建新的异常实例
     *
     * @param  object  $model
     * @param  string  $relation
     * @return static
     */
    public static function make($model, $relation)
    {
        $class = get_class($model);

        $instance = new static("Call to undefined relationship [{$relation}] on model [{$class}].");

        $instance->model = $class;
        $instance->relation = $relation;

        return $instance;
    }
}

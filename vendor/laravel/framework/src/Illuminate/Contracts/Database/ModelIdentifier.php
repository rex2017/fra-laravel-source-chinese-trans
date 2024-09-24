<?php
/**
 * 契约，数据库模型标识符接口
 */

namespace Illuminate\Contracts\Database;

class ModelIdentifier
{
    /**
     * The class name of the model.
	 * 模型类名
     *
     * @var string
     */
    public $class;

    /**
     * The unique identifier of the model.
	 * 模型唯一主键
     *
     * This may be either a single ID or an array of IDs.
     *
     * @var mixed
     */
    public $id;

    /**
     * The relationships loaded on the model.
	 * 模型关联关系
     *
     * @var array
     */
    public $relations;

    /**
     * The connection name of the model.
	 * 模型连接
     *
     * @var string|null
     */
    public $connection;

    /**
     * Create a new model identifier.
	 * 创建新的模型标识符
     *
     * @param  string  $class
     * @param  mixed  $id
     * @param  array  $relations
     * @param  mixed  $connection
     * @return void
     */
    public function __construct($class, $id, array $relations, $connection)
    {
        $this->id = $id;
        $this->class = $class;
        $this->relations = $relations;
        $this->connection = $connection;
    }
}

<?php
/**
 * 数据库，没有等待中迁移
 */

namespace Illuminate\Database\Events;

class NoPendingMigrations
{
    /**
     * The migration method that was called.
	 * 被调用迁移方法
     *
     * @var string
     */
    public $method;

    /**
     * Create a new event instance.
	 * 创建新的事件实例
     *
     * @param  string  $method
     * @return void
     */
    public function __construct($method)
    {
        $this->method = $method;
    }
}

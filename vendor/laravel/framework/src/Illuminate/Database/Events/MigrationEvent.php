<?php
/**
 * 数据库，迁移事件抽象类
 */

namespace Illuminate\Database\Events;

use Illuminate\Contracts\Database\Events\MigrationEvent as MigrationEventContract;
use Illuminate\Database\Migrations\Migration;

abstract class MigrationEvent implements MigrationEventContract
{
    /**
     * An migration instance.
	 * 迁移实例
     *
     * @var \Illuminate\Database\Migrations\Migration
     */
    public $migration;

    /**
     * The migration method that was called.
	 * 迁移方法被调用
     *
     * @var string
     */
    public $method;

    /**
     * Create a new event instance.
	 * 创建新的事件实例
     *
     * @param  \Illuminate\Database\Migrations\Migration  $migration
     * @param  string  $method
     * @return void
     */
    public function __construct(Migration $migration, $method)
    {
        $this->method = $method;
        $this->migration = $migration;
    }
}

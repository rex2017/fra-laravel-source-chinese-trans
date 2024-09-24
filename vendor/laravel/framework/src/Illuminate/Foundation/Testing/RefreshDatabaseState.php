<?php
/**
 * 基础，刷新数据库状态
 */

namespace Illuminate\Foundation\Testing;

class RefreshDatabaseState
{
    /**
     * Indicates if the test database has been migrated.
	 * 指明是否测试数据库已迁移
     *
     * @var bool
     */
    public static $migrated = false;
}

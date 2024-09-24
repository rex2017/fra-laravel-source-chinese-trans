<?php
/**
 * 基础，数据库迁移
 */

namespace Illuminate\Foundation\Testing;

use Illuminate\Contracts\Console\Kernel;

trait DatabaseMigrations
{
    /**
     * Define hooks to migrate the database before and after each test.
	 * 定义钩子，以便在每次测试之前和之后迁移数据库
     *
     * @return void
     */
    public function runDatabaseMigrations()
    {
        $this->artisan('migrate:fresh');

        $this->app[Kernel::class]->setArtisan(null);

        $this->beforeApplicationDestroyed(function () {
            $this->artisan('migrate:rollback');

            RefreshDatabaseState::$migrated = false;
        });
    }
}

<?php
/**
 * 基础，刷新数据库
 */

namespace Illuminate\Foundation\Testing;

use Illuminate\Contracts\Console\Kernel;

trait RefreshDatabase
{
    /**
     * Define hooks to migrate the database before and after each test.
	 * 定义钩子，以便在每次测试之前和之后迁移数据库
     *
     * @return void
     */
    public function refreshDatabase()
    {
        $this->usingInMemoryDatabase()
                        ? $this->refreshInMemoryDatabase()
                        : $this->refreshTestDatabase();
    }

    /**
     * Determine if an in-memory database is being used.
	 * 确定是否正在使用内存中的数据库
     *
     * @return bool
     */
    protected function usingInMemoryDatabase()
    {
        $default = config('database.default');

        return config("database.connections.$default.database") === ':memory:';
    }

    /**
     * Refresh the in-memory database.
	 * 刷新内存中的数据库
     *
     * @return void
     */
    protected function refreshInMemoryDatabase()
    {
        $this->artisan('migrate');

        $this->app[Kernel::class]->setArtisan(null);
    }

    /**
     * Refresh a conventional test database.
	 * 刷新常规测试数据库
     *
     * @return void
     */
    protected function refreshTestDatabase()
    {
        if (! RefreshDatabaseState::$migrated) {
            $this->artisan('migrate:fresh', [
                '--drop-views' => $this->shouldDropViews(),
                '--drop-types' => $this->shouldDropTypes(),
            ]);

            $this->app[Kernel::class]->setArtisan(null);

            RefreshDatabaseState::$migrated = true;
        }

        $this->beginDatabaseTransaction();
    }

    /**
     * Begin a database transaction on the testing database.
	 * 开始一个数据库事务在测试数据库上
     *
     * @return void
     */
    public function beginDatabaseTransaction()
    {
        $database = $this->app->make('db');

        foreach ($this->connectionsToTransact() as $name) {
            $connection = $database->connection($name);
            $dispatcher = $connection->getEventDispatcher();

            $connection->unsetEventDispatcher();
            $connection->beginTransaction();
            $connection->setEventDispatcher($dispatcher);
        }

        $this->beforeApplicationDestroyed(function () use ($database) {
            foreach ($this->connectionsToTransact() as $name) {
                $connection = $database->connection($name);
                $dispatcher = $connection->getEventDispatcher();

                $connection->unsetEventDispatcher();
                $connection->rollback();
                $connection->setEventDispatcher($dispatcher);
                $connection->disconnect();
            }
        });
    }

    /**
     * The database connections that should have transactions.
	 * 应该具有事务的数据库连接
     *
     * @return array
     */
    protected function connectionsToTransact()
    {
        return property_exists($this, 'connectionsToTransact')
                            ? $this->connectionsToTransact : [null];
    }

    /**
     * Determine if views should be dropped when refreshing the database.
	 * 确定在刷新数据库时是否应该删除视图
     *
     * @return bool
     */
    protected function shouldDropViews()
    {
        return property_exists($this, 'dropViews')
                            ? $this->dropViews : false;
    }

    /**
     * Determine if types should be dropped when refreshing the database.
	 * 确定在刷新数据库时是否应该删除类型
     *
     * @return bool
     */
    protected function shouldDropTypes()
    {
        return property_exists($this, 'dropTypes')
                            ? $this->dropTypes : false;
    }
}

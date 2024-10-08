<?php
/**
 * 基础，与数据库交互
 */

namespace Illuminate\Foundation\Testing\Concerns;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Testing\Constraints\HasInDatabase;
use Illuminate\Foundation\Testing\Constraints\SoftDeletedInDatabase;
use Illuminate\Support\Arr;
use PHPUnit\Framework\Constraint\LogicalNot as ReverseConstraint;

trait InteractsWithDatabase
{
    /**
     * Assert that a given where condition exists in the database.
	 * 断言给定的where条件存在于数据库中
     *
     * @param  string  $table
     * @param  array  $data
     * @param  string|null  $connection
     * @return $this
     */
    protected function assertDatabaseHas($table, array $data, $connection = null)
    {
        $this->assertThat(
            $table, new HasInDatabase($this->getConnection($connection), $data)
        );

        return $this;
    }

    /**
     * Assert that a given where condition does not exist in the database.
	 * 断言给定的where条件在数据库中不存在
     *
     * @param  string  $table
     * @param  array  $data
     * @param  string|null  $connection
     * @return $this
     */
    protected function assertDatabaseMissing($table, array $data, $connection = null)
    {
        $constraint = new ReverseConstraint(
            new HasInDatabase($this->getConnection($connection), $data)
        );

        $this->assertThat($table, $constraint);

        return $this;
    }

    /**
     * Assert the given record has been deleted.
	 * 断言给定的记录已被删除
     *
     * @param  \Illuminate\Database\Eloquent\Model|string  $table
     * @param  array  $data
     * @param  string|null  $connection
     * @return $this
     */
    protected function assertDeleted($table, array $data = [], $connection = null)
    {
        if ($table instanceof Model) {
            return $this->assertDatabaseMissing($table->getTable(), [$table->getKeyName() => $table->getKey()], $table->getConnectionName());
        }

        $this->assertDatabaseMissing($table, $data, $connection);

        return $this;
    }

    /**
     * Assert the given record has been "soft deleted".
	 * 断言给定的记录已被"软删除"
     *
     * @param  \Illuminate\Database\Eloquent\Model|string  $table
     * @param  array  $data
     * @param  string|null  $connection
     * @param  string|null  $deletedAtColumn
     * @return $this
     */
    protected function assertSoftDeleted($table, array $data = [], $connection = null, $deletedAtColumn = 'deleted_at')
    {
        if ($this->isSoftDeletableModel($table)) {
            return $this->assertSoftDeleted($table->getTable(), [$table->getKeyName() => $table->getKey()], $table->getConnectionName(), $table->getDeletedAtColumn());
        }

        $this->assertThat(
            $table, new SoftDeletedInDatabase($this->getConnection($connection), $data, $deletedAtColumn)
        );

        return $this;
    }

    /**
     * Determine if the argument is a soft deletable model.
	 * 确定参数是否是软可删除模型
     *
     * @param  mixed  $model
     * @return bool
     */
    protected function isSoftDeletableModel($model)
    {
        return $model instanceof Model
            && in_array(SoftDeletes::class, class_uses_recursive($model));
    }

    /**
     * Get the database connection.
	 * 得到数据库连接
     *
     * @param  string|null  $connection
     * @return \Illuminate\Database\Connection
     */
    protected function getConnection($connection = null)
    {
        $database = $this->app->make('db');

        $connection = $connection ?: $database->getDefaultConnection();

        return $database->connection($connection);
    }

    /**
     * Seed a given database connection.
	 * 为给定的数据库连接播种
     *
     * @param  array|string  $class
     * @return $this
     */
    public function seed($class = 'DatabaseSeeder')
    {
        foreach (Arr::wrap($class) as $class) {
            $this->artisan('db:seed', ['--class' => $class, '--no-interaction' => true]);
        }

        return $this;
    }
}

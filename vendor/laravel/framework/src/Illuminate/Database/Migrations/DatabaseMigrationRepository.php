<?php
/**
 * 数据库，数据库迁移仓库
 */

namespace Illuminate\Database\Migrations;

use Illuminate\Database\ConnectionResolverInterface as Resolver;

class DatabaseMigrationRepository implements MigrationRepositoryInterface
{
    /**
     * The database connection resolver instance.
	 * 数据库连接解析实例
     *
     * @var \Illuminate\Database\ConnectionResolverInterface
     */
    protected $resolver;

    /**
     * The name of the migration table.
	 * 迁移表名
     *
     * @var string
     */
    protected $table;

    /**
     * The name of the database connection to use.
	 * 数据库连接名
     *
     * @var string
     */
    protected $connection;

    /**
     * Create a new database migration repository instance.
	 * 创建新的迁移仓库实例
     *
     * @param  \Illuminate\Database\ConnectionResolverInterface  $resolver
     * @param  string  $table
     * @return void
     */
    public function __construct(Resolver $resolver, $table)
    {
        $this->table = $table;
        $this->resolver = $resolver;
    }

    /**
     * Get the completed migrations.
	 * 得到完成迁移
     *
     * @return array
     */
    public function getRan()
    {
        return $this->table()
                ->orderBy('batch', 'asc')
                ->orderBy('migration', 'asc')
                ->pluck('migration')->all();
    }

    /**
     * Get list of migrations.
	 * 得到迁移列表
     *
     * @param  int  $steps
     * @return array
     */
    public function getMigrations($steps)
    {
        $query = $this->table()->where('batch', '>=', '1');

        return $query->orderBy('batch', 'desc')
                     ->orderBy('migration', 'desc')
                     ->take($steps)->get()->all();
    }

    /**
     * Get the last migration batch.
	 * 得到最后迁移批处理
     *
     * @return array
     */
    public function getLast()
    {
        $query = $this->table()->where('batch', $this->getLastBatchNumber());

        return $query->orderBy('migration', 'desc')->get()->all();
    }

    /**
     * Get the completed migrations with their batch numbers.
	 * 得到已完成的迁移及其批号
     *
     * @return array
     */
    public function getMigrationBatches()
    {
        return $this->table()
                ->orderBy('batch', 'asc')
                ->orderBy('migration', 'asc')
                ->pluck('batch', 'migration')->all();
    }

    /**
     * Log that a migration was run.
	 * 记录迁移
     *
     * @param  string  $file
     * @param  int  $batch
     * @return void
     */
    public function log($file, $batch)
    {
        $record = ['migration' => $file, 'batch' => $batch];

        $this->table()->insert($record);
    }

    /**
     * Remove a migration from the log.
	 * 移除迁移从日志中
     *
     * @param  object  $migration
     * @return void
     */
    public function delete($migration)
    {
        $this->table()->where('migration', $migration->migration)->delete();
    }

    /**
     * Get the next migration batch number.
	 * 得到下一个迁移批号
     *
     * @return int
     */
    public function getNextBatchNumber()
    {
        return $this->getLastBatchNumber() + 1;
    }

    /**
     * Get the last migration batch number.
	 * 得到最后的迁移批号
     *
     * @return int
     */
    public function getLastBatchNumber()
    {
        return $this->table()->max('batch');
    }

    /**
     * Create the migration repository data store.
	 * 创建迁移仓库数据存储
     *
     * @return void
     */
    public function createRepository()
    {
        $schema = $this->getConnection()->getSchemaBuilder();

        $schema->create($this->table, function ($table) {
            // The migrations table is responsible for keeping track of which of the
            // migrations have actually run for the application. We'll create the
            // table to hold the migration file's path as well as the batch ID.
			// 迁移表负责跟踪应用程序实际运行了哪些迁移。我们将创建一个表来保存迁移文件的路径和批处理ID。
            $table->increments('id');
            $table->string('migration');
            $table->integer('batch');
        });
    }

    /**
     * Determine if the migration repository exists.
	 * 指明是否迁移仓库存在
     *
     * @return bool
     */
    public function repositoryExists()
    {
        $schema = $this->getConnection()->getSchemaBuilder();

        return $schema->hasTable($this->table);
    }

    /**
     * Get a query builder for the migration table.
	 * 得到迁移表的查询生成器
     *
     * @return \Illuminate\Database\Query\Builder
     */
    protected function table()
    {
        return $this->getConnection()->table($this->table)->useWritePdo();
    }

    /**
     * Get the connection resolver instance.
	 * 得到连接解析程序实例
     *
     * @return \Illuminate\Database\ConnectionResolverInterface
     */
    public function getConnectionResolver()
    {
        return $this->resolver;
    }

    /**
     * Resolve the database connection instance.
	 * 解析数据库连接实例
     *
     * @return \Illuminate\Database\Connection
     */
    public function getConnection()
    {
        return $this->resolver->connection($this->connection);
    }

    /**
     * Set the information source to gather data.
	 * 设置信息源以收集数据
     *
     * @param  string  $name
     * @return void
     */
    public function setSource($name)
    {
        $this->connection = $name;
    }
}

<?php
/**
 * 队列，数据库失败任务提供者
 */

namespace Illuminate\Queue\Failed;

use Illuminate\Database\ConnectionResolverInterface;
use Illuminate\Support\Facades\Date;

class DatabaseFailedJobProvider implements FailedJobProviderInterface
{
    /**
     * The connection resolver implementation.
	 * 连接解析实现
     *
     * @var \Illuminate\Database\ConnectionResolverInterface
     */
    protected $resolver;

    /**
     * The database connection name.
	 * 数据库连接名
     *
     * @var string
     */
    protected $database;

    /**
     * The database table.
	 * 数据库表
     *
     * @var string
     */
    protected $table;

    /**
     * Create a new database failed job provider.
	 * 创建新的数据库失败作业提供程序
     *
     * @param  \Illuminate\Database\ConnectionResolverInterface  $resolver
     * @param  string  $database
     * @param  string  $table
     * @return void
     */
    public function __construct(ConnectionResolverInterface $resolver, $database, $table)
    {
        $this->table = $table;
        $this->resolver = $resolver;
        $this->database = $database;
    }

    /**
     * Log a failed job into storage.
	 * 将失败的作业记录到存储中
     *
     * @param  string  $connection
     * @param  string  $queue
     * @param  string  $payload
     * @param  \Exception  $exception
     * @return int|null
     */
    public function log($connection, $queue, $payload, $exception)
    {
        $failed_at = Date::now();

        $exception = (string) $exception;

        return $this->getTable()->insertGetId(compact(
            'connection', 'queue', 'payload', 'exception', 'failed_at'
        ));
    }

    /**
     * Get a list of all of the failed jobs.
	 * 得到所有失败任务的列表
     *
     * @return array
     */
    public function all()
    {
        return $this->getTable()->orderBy('id', 'desc')->get()->all();
    }

    /**
     * Get a single failed job.
	 * 得到单个失败的作业
     *
     * @param  mixed  $id
     * @return object|null
     */
    public function find($id)
    {
        return $this->getTable()->find($id);
    }

    /**
     * Delete a single failed job from storage.
	 * 从存储中删除单个失败的作业
     *
     * @param  mixed  $id
     * @return bool
     */
    public function forget($id)
    {
        return $this->getTable()->where('id', $id)->delete() > 0;
    }

    /**
     * Flush all of the failed jobs from storage.
	 * 从存储中清除所有失败的作业
     *
     * @return void
     */
    public function flush()
    {
        $this->getTable()->delete();
    }

    /**
     * Get a new query builder instance for the table.
	 * 得到表的新查询生成器实例
     *
     * @return \Illuminate\Database\Query\Builder
     */
    protected function getTable()
    {
        return $this->resolver->connection($this->database)->table($this->table);
    }
}

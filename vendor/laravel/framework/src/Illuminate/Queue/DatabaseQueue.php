<?php
/**
 * 数据库队列
 */

namespace Illuminate\Queue;

use Illuminate\Contracts\Queue\Queue as QueueContract;
use Illuminate\Database\Connection;
use Illuminate\Queue\Jobs\DatabaseJob;
use Illuminate\Queue\Jobs\DatabaseJobRecord;
use Illuminate\Support\Carbon;
use PDO;

class DatabaseQueue extends Queue implements QueueContract
{
    /**
     * The database connection instance.
	 * 数据库连接实例
     *
     * @var \Illuminate\Database\Connection
     */
    protected $database;

    /**
     * The database table that holds the jobs.
	 * 保存作业的数据库表
     *
     * @var string
     */
    protected $table;

    /**
     * The name of the default queue.
	 * 默认队列名称
     *
     * @var string
     */
    protected $default;

    /**
     * The expiration time of a job.
	 * 作业过期的时间
     *
     * @var int|null
     */
    protected $retryAfter = 60;

    /**
     * Create a new database queue instance.
	 * 创建新的数据库队列实例
     *
     * @param  \Illuminate\Database\Connection  $database
     * @param  string  $table
     * @param  string  $default
     * @param  int  $retryAfter
     * @return void
     */
    public function __construct(Connection $database, $table, $default = 'default', $retryAfter = 60)
    {
        $this->table = $table;
        $this->default = $default;
        $this->database = $database;
        $this->retryAfter = $retryAfter;
    }

    /**
     * Get the size of the queue.
	 * 得到队列大小
     *
     * @param  string|null  $queue
     * @return int
     */
    public function size($queue = null)
    {
        return $this->database->table($this->table)
                    ->where('queue', $this->getQueue($queue))
                    ->count();
    }

    /**
     * Push a new job onto the queue.
	 * 推送新作业到队列中
     *
     * @param  string  $job
     * @param  mixed  $data
     * @param  string|null  $queue
     * @return mixed
     */
    public function push($job, $data = '', $queue = null)
    {
        return $this->pushToDatabase($queue, $this->createPayload(
            $job, $this->getQueue($queue), $data
        ));
    }

    /**
     * Push a raw payload onto the queue.
	 * 推入原始有效负载队列
     *
     * @param  string  $payload
     * @param  string|null  $queue
     * @param  array  $options
     * @return mixed
     */
    public function pushRaw($payload, $queue = null, array $options = [])
    {
        return $this->pushToDatabase($queue, $payload);
    }

    /**
     * Push a new job onto the queue after a delay.
	 * 推入新作业队列在延迟后
     *
     * @param  \DateTimeInterface|\DateInterval|int  $delay
     * @param  string  $job
     * @param  mixed  $data
     * @param  string|null  $queue
     * @return void
     */
    public function later($delay, $job, $data = '', $queue = null)
    {
        return $this->pushToDatabase($queue, $this->createPayload(
            $job, $this->getQueue($queue), $data
        ), $delay);
    }

    /**
     * Push an array of jobs onto the queue.
	 * 推入一组作业至队列
     *
     * @param  array  $jobs
     * @param  mixed  $data
     * @param  string|null  $queue
     * @return mixed
     */
    public function bulk($jobs, $data = '', $queue = null)
    {
        $queue = $this->getQueue($queue);

        $availableAt = $this->availableAt();

        return $this->database->table($this->table)->insert(collect((array) $jobs)->map(
            function ($job) use ($queue, $data, $availableAt) {
                return $this->buildDatabaseRecord($queue, $this->createPayload($job, $this->getQueue($queue), $data), $availableAt);
            }
        )->all());
    }

    /**
     * Release a reserved job back onto the queue.
	 * 释放预留的作业回队列
     *
     * @param  string  $queue
     * @param  \Illuminate\Queue\Jobs\DatabaseJobRecord  $job
     * @param  int  $delay
     * @return mixed
     */
    public function release($queue, $job, $delay)
    {
        return $this->pushToDatabase($queue, $job->payload, $delay, $job->attempts);
    }

    /**
     * Push a raw payload to the database with a given delay.
	 * 推送原始有效负载到数据库以给定的延迟
     *
     * @param  string|null  $queue
     * @param  string  $payload
     * @param  \DateTimeInterface|\DateInterval|int  $delay
     * @param  int  $attempts
     * @return mixed
     */
    protected function pushToDatabase($queue, $payload, $delay = 0, $attempts = 0)
    {
        return $this->database->table($this->table)->insertGetId($this->buildDatabaseRecord(
            $this->getQueue($queue), $payload, $this->availableAt($delay), $attempts
        ));
    }

    /**
     * Create an array to insert for the given job.
	 * 创建一个数组来插入给定的作业
     *
     * @param  string|null  $queue
     * @param  string  $payload
     * @param  int  $availableAt
     * @param  int  $attempts
     * @return array
     */
    protected function buildDatabaseRecord($queue, $payload, $availableAt, $attempts = 0)
    {
        return [
            'queue' => $queue,
            'attempts' => $attempts,
            'reserved_at' => null,
            'available_at' => $availableAt,
            'created_at' => $this->currentTime(),
            'payload' => $payload,
        ];
    }

    /**
     * Pop the next job off of the queue.
	 * 将下一个作业从队列中弹出
     *
     * @param  string|null  $queue
     * @return \Illuminate\Contracts\Queue\Job|null
     *
     * @throws \Exception|\Throwable
     */
    public function pop($queue = null)
    {
        $queue = $this->getQueue($queue);

        return $this->database->transaction(function () use ($queue) {
            if ($job = $this->getNextAvailableJob($queue)) {
                return $this->marshalJob($queue, $job);
            }
        });
    }

    /**
     * Get the next available job for the queue.
	 * 得到该队列的下一个可用作业
     *
     * @param  string|null  $queue
     * @return \Illuminate\Queue\Jobs\DatabaseJobRecord|null
     */
    protected function getNextAvailableJob($queue)
    {
        $job = $this->database->table($this->table)
                    ->lock($this->getLockForPopping())
                    ->where('queue', $this->getQueue($queue))
                    ->where(function ($query) {
                        $this->isAvailable($query);
                        $this->isReservedButExpired($query);
                    })
                    ->orderBy('id', 'asc')
                    ->first();

        return $job ? new DatabaseJobRecord((object) $job) : null;
    }

    /**
     * Get the lock required for popping the next job.
	 * 得到弹出下一个任务所需的锁
     *
     * @return string|bool
     */
    protected function getLockForPopping()
    {
        $databaseEngine = $this->database->getPdo()->getAttribute(PDO::ATTR_DRIVER_NAME);
        $databaseVersion = $this->database->getPdo()->getAttribute(PDO::ATTR_SERVER_VERSION);

        if ($databaseEngine == 'mysql' && ! strpos($databaseVersion, 'MariaDB') && version_compare($databaseVersion, '8.0.1', '>=') ||
            $databaseEngine == 'pgsql' && version_compare($databaseVersion, '9.5', '>=')) {
            return 'FOR UPDATE SKIP LOCKED';
        }

        return true;
    }

    /**
     * Modify the query to check for available jobs.
	 * 修改查询以检查可用的作业
     *
     * @param  \Illuminate\Database\Query\Builder  $query
     * @return void
     */
    protected function isAvailable($query)
    {
        $query->where(function ($query) {
            $query->whereNull('reserved_at')
                  ->where('available_at', '<=', $this->currentTime());
        });
    }

    /**
     * Modify the query to check for jobs that are reserved but have expired.
	 * 修改查询以检查保留但已过期的作业
     *
     * @param  \Illuminate\Database\Query\Builder  $query
     * @return void
     */
    protected function isReservedButExpired($query)
    {
        $expiration = Carbon::now()->subSeconds($this->retryAfter)->getTimestamp();

        $query->orWhere(function ($query) use ($expiration) {
            $query->where('reserved_at', '<=', $expiration);
        });
    }

    /**
     * Marshal the reserved job into a DatabaseJob instance.
	 * 将保留的作业封送到DatabaseJob实例中
     *
     * @param  string  $queue
     * @param  \Illuminate\Queue\Jobs\DatabaseJobRecord  $job
     * @return \Illuminate\Queue\Jobs\DatabaseJob
     */
    protected function marshalJob($queue, $job)
    {
        $job = $this->markJobAsReserved($job);

        return new DatabaseJob(
            $this->container, $this, $job, $this->connectionName, $queue
        );
    }

    /**
     * Mark the given job ID as reserved.
	 * 将给定的作业ID标记为保留
     *
     * @param  \Illuminate\Queue\Jobs\DatabaseJobRecord  $job
     * @return \Illuminate\Queue\Jobs\DatabaseJobRecord
     */
    protected function markJobAsReserved($job)
    {
        $this->database->table($this->table)->where('id', $job->id)->update([
            'reserved_at' => $job->touch(),
            'attempts' => $job->increment(),
        ]);

        return $job;
    }

    /**
     * Delete a reserved job from the queue.
	 * 删除保留的作业从队列
     *
     * @param  string  $queue
     * @param  string  $id
     * @return void
     *
     * @throws \Exception|\Throwable
     */
    public function deleteReserved($queue, $id)
    {
        $this->database->transaction(function () use ($id) {
            if ($this->database->table($this->table)->lockForUpdate()->find($id)) {
                $this->database->table($this->table)->where('id', $id)->delete();
            }
        });
    }

    /**
     * Get the queue or return the default.
	 * 得到队列或返回默认值
     *
     * @param  string|null  $queue
     * @return string
     */
    public function getQueue($queue)
    {
        return $queue ?: $this->default;
    }

    /**
     * Get the underlying database instance.
	 * 得到底层数据库实例
     *
     * @return \Illuminate\Database\Connection
     */
    public function getDatabase()
    {
        return $this->database;
    }
}

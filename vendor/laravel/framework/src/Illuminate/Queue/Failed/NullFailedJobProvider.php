<?php
/**
 * 队列，空失败作业提供者
 */

namespace Illuminate\Queue\Failed;

class NullFailedJobProvider implements FailedJobProviderInterface
{
    /**
     * Log a failed job into storage.
	 * 记录一个失败作业至存储
     *
     * @param  string  $connection
     * @param  string  $queue
     * @param  string  $payload
     * @param  \Exception  $exception
     * @return int|null
     */
    public function log($connection, $queue, $payload, $exception)
    {
        //
    }

    /**
     * Get a list of all of the failed jobs.
	 * 得到一个所有失败作业列表 
     *
     * @return array
     */
    public function all()
    {
        return [];
    }

    /**
     * Get a single failed job.
	 * 得到单个失败作业
     *
     * @param  mixed  $id
     * @return object|null
     */
    public function find($id)
    {
        //
    }

    /**
     * Delete a single failed job from storage.
	 * 删除单个失败作业从存储
     *
     * @param  mixed  $id
     * @return bool
     */
    public function forget($id)
    {
        return true;
    }

    /**
     * Flush all of the failed jobs from storage.
	 * 清除所有失败作业从存储
     *
     * @return void
     */
    public function flush()
    {
        //
    }
}

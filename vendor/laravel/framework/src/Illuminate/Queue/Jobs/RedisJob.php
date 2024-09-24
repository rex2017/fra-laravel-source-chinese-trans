<?php
/**
 * 队列，Redis作业
 */

namespace Illuminate\Queue\Jobs;

use Illuminate\Container\Container;
use Illuminate\Contracts\Queue\Job as JobContract;
use Illuminate\Queue\RedisQueue;

class RedisJob extends Job implements JobContract
{
    /**
     * The Redis queue instance.
	 * Redis队列实例
     *
     * @var \Illuminate\Queue\RedisQueue
     */
    protected $redis;

    /**
     * The Redis raw job payload.
	 * Redis原始作业负载
     *
     * @var string
     */
    protected $job;

    /**
     * The JSON decoded version of "$job".
	 * JSON解码版本
     *
     * @var array
     */
    protected $decoded;

    /**
     * The Redis job payload inside the reserved queue.
	 * Redis作业负载在预留队列内
     *
     * @var string
     */
    protected $reserved;

    /**
     * Create a new job instance.
	 * 创建新的作业实例
     *
     * @param  \Illuminate\Container\Container  $container
     * @param  \Illuminate\Queue\RedisQueue  $redis
     * @param  string  $job
     * @param  string  $reserved
     * @param  string  $connectionName
     * @param  string  $queue
     * @return void
     */
    public function __construct(Container $container, RedisQueue $redis, $job, $reserved, $connectionName, $queue)
    {
        // The $job variable is the original job JSON as it existed in the ready queue while
        // the $reserved variable is the raw JSON in the reserved queue. The exact format
        // of the reserved job is required in order for us to properly delete its data.
		// $job变量是就绪队列中存在的原始作业JSON，而$reserved变量是保留队列中的原始JSON。
		// 需要保留作业的确切格式，以便我们正确删除其数据。
        $this->job = $job;
        $this->redis = $redis;
        $this->queue = $queue;
        $this->reserved = $reserved;
        $this->container = $container;
        $this->connectionName = $connectionName;

        $this->decoded = $this->payload();
    }

    /**
     * Get the raw body string for the job.
	 * 得到工作的原始主体字符串
     *
     * @return string
     */
    public function getRawBody()
    {
        return $this->job;
    }

    /**
     * Delete the job from the queue.
	 * 删除作业从队列
     *
     * @return void
     */
    public function delete()
    {
        parent::delete();

        $this->redis->deleteReserved($this->queue, $this);
    }

    /**
     * Release the job back into the queue.
	 * 释放作业回队列
     *
     * @param  int  $delay
     * @return void
     */
    public function release($delay = 0)
    {
        parent::release($delay);

        $this->redis->deleteAndRelease($this->queue, $this, $delay);
    }

    /**
     * Get the number of times the job has been attempted.
	 * 得到该任务被尝试的次数
     *
     * @return int
     */
    public function attempts()
    {
        return ($this->decoded['attempts'] ?? null) + 1;
    }

    /**
     * Get the job identifier.
	 * 得到作业标识符
     *
     * @return string
     */
    public function getJobId()
    {
        return $this->decoded['id'] ?? null;
    }

    /**
     * Get the underlying Redis factory implementation.
	 * 得到底层Redis工厂实现
     *
     * @return \Illuminate\Queue\RedisQueue
     */
    public function getRedisQueue()
    {
        return $this->redis;
    }

    /**
     * Get the underlying reserved Redis job.
	 * 得到底层预留的Redis作业
     *
     * @return string
     */
    public function getReservedJob()
    {
        return $this->reserved;
    }
}

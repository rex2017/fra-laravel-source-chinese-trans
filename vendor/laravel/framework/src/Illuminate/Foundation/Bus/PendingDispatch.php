<?php
/**
 * 基础，总线等待调度
 */

namespace Illuminate\Foundation\Bus;

use Illuminate\Contracts\Bus\Dispatcher;

class PendingDispatch
{
    /**
     * The job.
	 * 作业
     *
     * @var mixed
     */
    protected $job;

    /**
     * Create a new pending job dispatch.
	 * 创建新的等待任务
     *
     * @param  mixed  $job
     * @return void
     */
    public function __construct($job)
    {
        $this->job = $job;
    }

    /**
     * Set the desired connection for the job.
	 * 设置所需的连接为作业
     *
     * @param  string|null  $connection
     * @return $this
     */
    public function onConnection($connection)
    {
        $this->job->onConnection($connection);

        return $this;
    }

    /**
     * Set the desired queue for the job.
	 * 设置所需的队列为作业
     *
     * @param  string|null  $queue
     * @return $this
     */
    public function onQueue($queue)
    {
        $this->job->onQueue($queue);

        return $this;
    }

    /**
     * Set the desired connection for the chain.
	 * 设置所需的连接为链条
     *
     * @param  string|null  $connection
     * @return $this
     */
    public function allOnConnection($connection)
    {
        $this->job->allOnConnection($connection);

        return $this;
    }

    /**
     * Set the desired queue for the chain.
	 * 设置所需的队列为链
     *
     * @param  string|null  $queue
     * @return $this
     */
    public function allOnQueue($queue)
    {
        $this->job->allOnQueue($queue);

        return $this;
    }

    /**
     * Set the desired delay for the job.
	 * 设置所需的延迟为作业
     *
     * @param  \DateTimeInterface|\DateInterval|int|null  $delay
     * @return $this
     */
    public function delay($delay)
    {
        $this->job->delay($delay);

        return $this;
    }

    /**
     * Set the jobs that should run if this job is successful.
	 * 设置应该运行的作业当作业成功时
	 * 
     *
     * @param  array  $chain
     * @return $this
     */
    public function chain($chain)
    {
        $this->job->chain($chain);

        return $this;
    }

    /**
     * Handle the object's destruction.
	 * 处理对象的销毁
     *
     * @return void
     */
    public function __destruct()
    {
        app(Dispatcher::class)->dispatch($this->job);
    }
}

<?php
/**
 * 基础总线，等待分派
 */

namespace Illuminate\Foundation\Bus;

use Illuminate\Contracts\Bus\Dispatcher;

class PendingDispatch
{
    /**
     * The job.
	 * 任务
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
     *
     * @return void
     */
    public function __destruct()
    {
        app(Dispatcher::class)->dispatch($this->job);
    }
}

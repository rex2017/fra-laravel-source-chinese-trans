<?php
/**
 * 总线队列
 */

namespace Illuminate\Bus;

use Illuminate\Support\Arr;

trait Queueable
{
    /**
     * The name of the connection the job should be sent to.
	 * 连接名称应该将作业发送到的
     *
     * @var string|null
     */
    public $connection;

    /**
     * The name of the queue the job should be sent to.
	 * 队列名应该将作业发送到队列
     *
     * @var string|null
     */
    public $queue;

    /**
     * The name of the connection the chain should be sent to.
	 * 连接名应该将链发送到连接
     *
     * @var string|null
     */
    public $chainConnection;

    /**
     * The name of the queue the chain should be sent to.
	 * 队列名应该将链发送到队列
     *
     * @var string|null
     */
    public $chainQueue;

    /**
     * The number of seconds before the job should be made available.
	 * 在作业可用之前的秒数
     *
     * @var \DateTimeInterface|\DateInterval|int|null
     */
    public $delay;

    /**
     * The middleware the job should be dispatched through.
	 * 中间件作业应该通过分派的
     */
    public $middleware = [];

    /**
     * The jobs that should run if this job is successful.
	 * 应该运行的作业如果此作业成功的
     *
     * @var array
     */
    public $chained = [];

    /**
     * Set the desired connection for the job.
	 * 设置所需的连接为任务
     *
     * @param  string|null  $connection
     * @return $this
     */
    public function onConnection($connection)
    {
        $this->connection = $connection;

        return $this;
    }

    /**
     * Set the desired queue for the job.
	 * 设置作业所需的队列
     *
     * @param  string|null  $queue
     * @return $this
     */
    public function onQueue($queue)
    {
        $this->queue = $queue;

        return $this;
    }

    /**
     * Set the desired connection for the chain.
	 * 设置链所需的连接
     *
     * @param  string|null  $connection
     * @return $this
     */
    public function allOnConnection($connection)
    {
        $this->chainConnection = $connection;
        $this->connection = $connection;

        return $this;
    }

    /**
     * Set the desired queue for the chain.
	 * 设置链所需的队列
     *
     * @param  string|null  $queue
     * @return $this
     */
    public function allOnQueue($queue)
    {
        $this->chainQueue = $queue;
        $this->queue = $queue;

        return $this;
    }

    /**
     * Set the desired delay for the job.
	 * 设置作业所需的延迟
     *
     * @param  \DateTimeInterface|\DateInterval|int|null  $delay
     * @return $this
     */
    public function delay($delay)
    {
        $this->delay = $delay;

        return $this;
    }

    /**
     * Get the middleware the job should be dispatched through.
	 * 得到作业应该被分派的中间件
     *
     * @return array
     */
    public function middleware()
    {
        return [];
    }

    /**
     * Specify the middleware the job should be dispatched through.
	 * 指定应该通过哪个中间件分派作业
     *
     * @param  array|object  $middleware
     * @return $this
     */
    public function through($middleware)
    {
        $this->middleware = Arr::wrap($middleware);

        return $this;
    }

    /**
     * Set the jobs that should run if this job is successful.
	 * 设置作业成功时应该运行的作业
     *
     * @param  array  $chain
     * @return $this
     */
    public function chain($chain)
    {
        $this->chained = collect($chain)->map(function ($job) {
            return serialize($job);
        })->all();

        return $this;
    }

    /**
     * Dispatch the next job on the chain.
	 * 执行链条上的下一任务
     *
     * @return void
     */
    public function dispatchNextJobInChain()
    {
        if (! empty($this->chained)) {
            dispatch(tap(unserialize(array_shift($this->chained)), function ($next) {
                $next->chained = $this->chained;

                $next->onConnection($next->connection ?: $this->chainConnection);
                $next->onQueue($next->queue ?: $this->chainQueue);

                $next->chainConnection = $this->chainConnection;
                $next->chainQueue = $this->chainQueue;
            }));
        }
    }
}

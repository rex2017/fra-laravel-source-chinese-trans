<?php
/**
 * 契约，队列接口
 */

namespace Illuminate\Contracts\Queue;

interface Queue
{
    /**
     * Get the size of the queue.
	 * 得到队列大小
     *
     * @param  string|null  $queue
     * @return int
     */
    public function size($queue = null);

    /**
     * Push a new job onto the queue.
	 * 推入新作业入队列
     *
     * @param  string|object  $job
     * @param  mixed  $data
     * @param  string|null  $queue
     * @return mixed
     */
    public function push($job, $data = '', $queue = null);

    /**
     * Push a new job onto the queue.
	 * 推入新作业入队列
     *
     * @param  string  $queue
     * @param  string|object  $job
     * @param  mixed  $data
     * @return mixed
     */
    public function pushOn($queue, $job, $data = '');

    /**
     * Push a raw payload onto the queue.
	 * 推入原始有效负载至队列
     *
     * @param  string  $payload
     * @param  string|null  $queue
     * @param  array  $options
     * @return mixed
     */
    public function pushRaw($payload, $queue = null, array $options = []);

    /**
     * Push a new job onto the queue after a delay.
	 * 推入新作业至队列使用延迟
     *
     * @param  \DateTimeInterface|\DateInterval|int  $delay
     * @param  string|object  $job
     * @param  mixed  $data
     * @param  string|null  $queue
     * @return mixed
     */
    public function later($delay, $job, $data = '', $queue = null);

    /**
     * Push a new job onto the queue after a delay.
	 * 推入新任务至队列使用延迟
     *
     * @param  string  $queue
     * @param  \DateTimeInterface|\DateInterval|int  $delay
     * @param  string|object  $job
     * @param  mixed  $data
     * @return mixed
     */
    public function laterOn($queue, $delay, $job, $data = '');

    /**
     * Push an array of jobs onto the queue.
	 * 推入任务数组至队列
     *
     * @param  array  $jobs
     * @param  mixed  $data
     * @param  string|null  $queue
     * @return mixed
     */
    public function bulk($jobs, $data = '', $queue = null);

    /**
     * Pop the next job off of the queue.
	 * 取出下一个任务从队列中
     *
     * @param  string  $queue
     * @return \Illuminate\Contracts\Queue\Job|null
     */
    public function pop($queue = null);

    /**
     * Get the connection name for the queue.
	 * 得到队列连接名
     *
     * @return string
     */
    public function getConnectionName();

    /**
     * Set the connection name for the queue.
	 * 设置队列连接名
     *
     * @param  string  $name
     * @return $this
     */
    public function setConnectionName($name);
}

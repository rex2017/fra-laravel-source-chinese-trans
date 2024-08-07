<?php
/**
 * 契约，队列监听接口
 */

namespace Illuminate\Contracts\Queue;

interface Monitor
{
    /**
     * Register a callback to be executed on every iteration through the queue loop.
	 * 注册一个回调函数，在队列循环的每次迭代中执行
     *
     * @param  mixed  $callback
     * @return void
     */
    public function looping($callback);

    /**
     * Register a callback to be executed when a job fails after the maximum amount of retries.
	 * 注册一个回调函数，在任务最大尝试失败后执行
     *
     * @param  mixed  $callback
     * @return void
     */
    public function failing($callback);

    /**
     * Register a callback to be executed when a daemon queue is stopping.
	 * 注册一个回调函数，在任务停止时执行
     *
     * @param  mixed  $callback
     * @return void
     */
    public function stopping($callback);
}

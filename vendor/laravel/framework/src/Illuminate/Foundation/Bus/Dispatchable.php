<?php
/**
 * 基础，总线调度
 */

namespace Illuminate\Foundation\Bus;

use Illuminate\Contracts\Bus\Dispatcher;

trait Dispatchable
{
    /**
     * Dispatch the job with the given arguments.
	 * 调度任务用给定参数
     *
     * @return \Illuminate\Foundation\Bus\PendingDispatch
     */
    public static function dispatch()
    {
        return new PendingDispatch(new static(...func_get_args()));
    }

    /**
     * Dispatch a command to its appropriate handler in the current process.
	 * 将命令分派给当前进程中相应的处理程序
     *
     * @return mixed
     */
    public static function dispatchNow()
    {
        return app(Dispatcher::class)->dispatchNow(new static(...func_get_args()));
    }

    /**
     * Dispatch a command to its appropriate handler after the current process.
	 * 在当前进程结束后，将命令分派给相应的处理程序
     *
     * @return mixed
     */
    public static function dispatchAfterResponse()
    {
        return app(Dispatcher::class)->dispatchAfterResponse(new static(...func_get_args()));
    }

    /**
     * Set the jobs that should run if this job is successful.
	 * 设置作业成功时应该运行的作业
     *
     * @param  array  $chain
     * @return \Illuminate\Foundation\Bus\PendingChain
     */
    public static function withChain($chain)
    {
        return new PendingChain(static::class, $chain);
    }
}

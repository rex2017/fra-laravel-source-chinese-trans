<?php
/**
 * 基础，总线调度任务
 */

namespace Illuminate\Foundation\Bus;

use Illuminate\Contracts\Bus\Dispatcher;

trait DispatchesJobs
{
    /**
     * Dispatch a job to its appropriate handler.
	 * 分派任务至对应的处理程序
     *
     * @param  mixed  $job
     * @return mixed
     */
    protected function dispatch($job)
    {
        return app(Dispatcher::class)->dispatch($job);
    }

    /**
     * Dispatch a job to its appropriate handler in the current process.
	 * 分派作业给当前进程中相应的处理程序
     *
     * @param  mixed  $job
     * @return mixed
     */
    public function dispatchNow($job)
    {
        return app(Dispatcher::class)->dispatchNow($job);
    }
}

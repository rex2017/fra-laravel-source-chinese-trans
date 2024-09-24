<?php
/**
 * 契约，总线队列调度接口
 */

namespace Illuminate\Contracts\Bus;

interface QueueingDispatcher extends Dispatcher
{
    /**
     * Dispatch a command to its appropriate handler behind a queue.
	 * 分派命令到队列后面的相应处理程序
     *
     * @param  mixed  $command
     * @return mixed
     */
    public function dispatchToQueue($command);
}

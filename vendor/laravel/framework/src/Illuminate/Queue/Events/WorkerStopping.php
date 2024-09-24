<?php
/**
 * 队列，执行停止
 */

namespace Illuminate\Queue\Events;

class WorkerStopping
{
    /**
     * The exit status.
	 * 中止状态
     *
     * @var int
     */
    public $status;

    /**
     * Create a new event instance.
	 * 创建新的事件实例
     *
     * @param  int  $status
     * @return void
     */
    public function __construct($status = 0)
    {
        $this->status = $status;
    }
}

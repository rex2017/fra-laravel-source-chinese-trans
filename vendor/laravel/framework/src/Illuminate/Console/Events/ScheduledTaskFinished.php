<?php
/**
 * 控制台，计划任务完成
 */

namespace Illuminate\Console\Events;

use Illuminate\Console\Scheduling\Event;

class ScheduledTaskFinished
{
    /**
     * The scheduled event that ran.
	 * 已运行计划事件
     *
     * @var \Illuminate\Console\Scheduling\Event
     */
    public $task;

    /**
     * The runtime of the scheduled event.
	 * 计划事件的运行时间
     *
     * @var float
     */
    public $runtime;

    /**
     * Create a new event instance.
	 * 创建新的事件实例
     *
     * @param  \Illuminate\Console\Scheduling\Event  $task
     * @param  float  $runtime
     * @return void
     */
    public function __construct(Event $task, $runtime)
    {
        $this->task = $task;
        $this->runtime = $runtime;
    }
}

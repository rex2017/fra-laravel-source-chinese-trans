<?php
/**
 * 控制台，计划任务启动
 */

namespace Illuminate\Console\Events;

use Illuminate\Console\Scheduling\Event;

class ScheduledTaskStarting
{
    /**
     * The scheduled event being run.
	 * 正在运行的计划事件
     *
     * @var \Illuminate\Console\Scheduling\Event
     */
    public $task;

    /**
     * Create a new event instance.
	 * 创建新的事件实例
     *
     * @param  \Illuminate\Console\Scheduling\Event  $task
     * @return void
     */
    public function __construct(Event $task)
    {
        $this->task = $task;
    }
}

<?php
/**
 * 控制台，事件互斥
 */

namespace Illuminate\Console\Scheduling;

interface EventMutex
{
    /**
     * Attempt to obtain an event mutex for the given event.
	 * 尝试获取给定事件的事件互斥锁
     *
     * @param  \Illuminate\Console\Scheduling\Event  $event
     * @return bool
     */
    public function create(Event $event);

    /**
     * Determine if an event mutex exists for the given event.
	 * 确定给定事件是否存在事件互斥锁
     *
     * @param  \Illuminate\Console\Scheduling\Event  $event
     * @return bool
     */
    public function exists(Event $event);

    /**
     * Clear the event mutex for the given event.
	 * 清除给定事件的事件互斥锁
     *
     * @param  \Illuminate\Console\Scheduling\Event  $event
     * @return void
     */
    public function forget(Event $event);
}

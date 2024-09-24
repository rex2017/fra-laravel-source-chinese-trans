<?php
/**
 * 控制台，调度互斥
 */

namespace Illuminate\Console\Scheduling;

use DateTimeInterface;

interface SchedulingMutex
{
    /**
     * Attempt to obtain a scheduling mutex for the given event.
	 * 尝试获取给定事件的调度互斥锁
     *
     * @param  \Illuminate\Console\Scheduling\Event  $event
     * @param  \DateTimeInterface  $time
     * @return bool
     */
    public function create(Event $event, DateTimeInterface $time);

    /**
     * Determine if a scheduling mutex exists for the given event.
	 * 确定是否存在给定事件的调度互斥锁
     *
     * @param  \Illuminate\Console\Scheduling\Event  $event
     * @param  \DateTimeInterface  $time
     * @return bool
     */
    public function exists(Event $event, DateTimeInterface $time);
}

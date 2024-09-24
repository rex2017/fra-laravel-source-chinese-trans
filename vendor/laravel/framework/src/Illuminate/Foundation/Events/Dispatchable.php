<?php
/**
 * 基础事件，调度
 */

namespace Illuminate\Foundation\Events;

trait Dispatchable
{
    /**
     * Dispatch the event with the given arguments.
	 * 调度事件使用给定的参数
     *
     * @return void
     */
    public static function dispatch()
    {
        return event(new static(...func_get_args()));
    }

    /**
     * Broadcast the event with the given arguments.
	 * 广播事件使用给定参数
     *
     * @return \Illuminate\Broadcasting\PendingBroadcast
     */
    public static function broadcast()
    {
        return broadcast(new static(...func_get_args()));
    }
}

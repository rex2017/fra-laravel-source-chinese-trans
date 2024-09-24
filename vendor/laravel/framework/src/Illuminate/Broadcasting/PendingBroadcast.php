<?php
/**
 * 等待中广播
 */

namespace Illuminate\Broadcasting;

use Illuminate\Contracts\Events\Dispatcher;

class PendingBroadcast
{
    /**
     * The event dispatcher implementation.
	 * 事件调度实现
     *
     * @var \Illuminate\Contracts\Events\Dispatcher
     */
    protected $events;

    /**
     * The event instance.
	 * 事件实例
     *
     * @var mixed
     */
    protected $event;

    /**
     * Create a new pending broadcast instance.
	 * 创建新的待处理广播实例
     *
     * @param  \Illuminate\Contracts\Events\Dispatcher  $events
     * @param  mixed  $event
     * @return void
     */
    public function __construct(Dispatcher $events, $event)
    {
        $this->event = $event;
        $this->events = $events;
    }

    /**
     * Broadcast the event to everyone except the current user.
	 * 广播事件给除当前用户之外的所有人
     *
     * @return $this
     */
    public function toOthers()
    {
        if (method_exists($this->event, 'dontBroadcastToCurrentUser')) {
            $this->event->dontBroadcastToCurrentUser();
        }

        return $this;
    }

    /**
     * Handle the object's destruction.
	 * 对象销毁
     *
     * @return void
     */
    public function __destruct()
    {
        $this->events->dispatch($this->event);
    }
}

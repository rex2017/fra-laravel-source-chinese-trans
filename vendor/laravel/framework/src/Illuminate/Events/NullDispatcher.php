<?php
/**
 * 事件空调度
 */

namespace Illuminate\Events;

use Illuminate\Contracts\Events\Dispatcher as DispatcherContract;
use Illuminate\Support\Traits\ForwardsCalls;

class NullDispatcher implements DispatcherContract
{
    use ForwardsCalls;

    /**
     * The underlying event dispatcher instance.
	 * 底层事件调度实例
     *
     * @var \Illuminate\Contracts\Bus\Dispatcher
     */
    protected $dispatcher;

    /**
     * Create a new event dispatcher instance that does not fire.
	 * 创建一个不触发的新事件调度程序实例
     *
     * @param  \Illuminate\Contracts\Events\Dispatcher  $dispatcher
     * @return void
     */
    public function __construct(DispatcherContract $dispatcher)
    {
        $this->dispatcher = $dispatcher;
    }

    /**
     * Don't fire an event.
	 * 不要触发一个事件
     *
     * @param  string|object  $event
     * @param  mixed  $payload
     * @param  bool  $halt
     * @return void
     */
    public function dispatch($event, $payload = [], $halt = false)
    {
    }

    /**
     * Don't register an event and payload to be fired later.
	 * 不要将事件和有效载荷注册为稍后触发
     *
     * @param  string  $event
     * @param  array  $payload
     * @return void
     */
    public function push($event, $payload = [])
    {
    }

    /**
     * Don't dispatch an event.
	 * 不要调度事件
     *
     * @param  string|object  $event
     * @param  mixed  $payload
     * @return array|null
     */
    public function until($event, $payload = [])
    {
    }

    /**
     * Register an event listener with the dispatcher.
	 * 注册一个监听事件用调度器
     *
     * @param  string|array  $events
     * @param  \Closure|string  $listener
     * @return void
     */
    public function listen($events, $listener)
    {
        $this->dispatcher->listen($events, $listener);
    }

    /**
     * Determine if a given event has listeners.
	 * 指明是否给定事件有监听者
     *
     * @param  string  $eventName
     * @return bool
     */
    public function hasListeners($eventName)
    {
        return $this->dispatcher->hasListeners($eventName);
    }

    /**
     * Register an event subscriber with the dispatcher.
	 * 注册事件订阅者向调度程序
     *
     * @param  object|string  $subscriber
     * @return void
     */
    public function subscribe($subscriber)
    {
        $this->dispatcher->subscribe($subscriber);
    }

    /**
     * Flush a set of pushed events.
	 * 刷新事件
     *
     * @param  string  $event
     * @return void
     */
    public function flush($event)
    {
        $this->dispatcher->flush($event);
    }

    /**
     * Remove a set of listeners from the dispatcher.
	 * 删除一组侦听器从调度程序中
     *
     * @param  string  $event
     * @return void
     */
    public function forget($event)
    {
        $this->dispatcher->forget($event);
    }

    /**
     * Forget all of the queued listeners.
	 * 忘记所有队列监听者
     *
     * @return void
     */
    public function forgetPushed()
    {
        $this->dispatcher->forgetPushed();
    }

    /**
     * Dynamically pass method calls to the underlying dispatcher.
	 * 动态调取方法
     *
     * @param  string  $method
     * @param  array  $parameters
     * @return mixed
     */
    public function __call($method, $parameters)
    {
        return $this->forwardCallTo($this->dispatcher, $method, $parameters);
    }
}

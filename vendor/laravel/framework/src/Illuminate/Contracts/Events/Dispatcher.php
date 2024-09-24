<?php
/**
 * 契约，事件调度接口
 */

namespace Illuminate\Contracts\Events;

interface Dispatcher
{
    /**
     * Register an event listener with the dispatcher.
	 * 注册一个事件监听器
     *
     * @param  string|array  $events
     * @param  \Closure|string  $listener
     * @return void
     */
    public function listen($events, $listener);

    /**
     * Determine if a given event has listeners.
	 * 确定给定事件是否有监听器
     *
     * @param  string  $eventName
     * @return bool
     */
    public function hasListeners($eventName);

    /**
     * Register an event subscriber with the dispatcher.
	 * 注册事件订阅者使用调度程序
     *
     * @param  object|string  $subscriber
     * @return void
     */
    public function subscribe($subscriber);

    /**
     * Dispatch an event until the first non-null response is returned.
	 * 调度一个事件，直到返回第一个非空响应
     *
     * @param  string|object  $event
     * @param  mixed  $payload
     * @return array|null
     */
    public function until($event, $payload = []);

    /**
     * Dispatch an event and call the listeners.
	 * 分派事件并调用监听器
     *
     * @param  string|object  $event
     * @param  mixed  $payload
     * @param  bool  $halt
     * @return array|null
     */
    public function dispatch($event, $payload = [], $halt = false);

    /**
     * Register an event and payload to be fired later.
	 * 注册事件并延迟启动
     *
     * @param  string  $event
     * @param  array  $payload
     * @return void
     */
    public function push($event, $payload = []);

    /**
     * Flush a set of pushed events.
	 * 刷新一组推送的事件
     *
     * @param  string  $event
     * @return void
     */
    public function flush($event);

    /**
     * Remove a set of listeners from the dispatcher.
	 * 删除一组监听器从调度中
     *
     * @param  string  $event
     * @return void
     */
    public function forget($event);

    /**
     * Forget all of the queued listeners.
	 * 注销所有排队的监听器
     *
     * @return void
     */
    public function forgetPushed();
}

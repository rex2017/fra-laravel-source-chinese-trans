<?php
/**
 * 支持，事件伪造
 */

namespace Illuminate\Support\Testing\Fakes;

use Closure;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Support\Arr;
use PHPUnit\Framework\Assert as PHPUnit;

class EventFake implements Dispatcher
{
    /**
     * The original event dispatcher.
	 * 原始事件调度程序
     *
     * @var \Illuminate\Contracts\Events\Dispatcher
     */
    protected $dispatcher;

    /**
     * The event types that should be intercepted instead of dispatched.
	 * 应该拦截而不是分派的事件类型
     *
     * @var array
     */
    protected $eventsToFake;

    /**
     * All of the events that have been intercepted keyed by type.
	 * 所有被截获的事件都是按类型键入的
     *
     * @var array
     */
    protected $events = [];

    /**
     * Create a new event fake instance.
	 * 创建新的事件伪实例
     *
     * @param  \Illuminate\Contracts\Events\Dispatcher  $dispatcher
     * @param  array|string  $eventsToFake
     * @return void
     */
    public function __construct(Dispatcher $dispatcher, $eventsToFake = [])
    {
        $this->dispatcher = $dispatcher;

        $this->eventsToFake = Arr::wrap($eventsToFake);
    }

    /**
     * Assert if an event was dispatched based on a truth-test callback.
	 * 判断事件是否基于真值测试回调分派
     *
     * @param  string  $event
     * @param  callable|int|null  $callback
     * @return void
     */
    public function assertDispatched($event, $callback = null)
    {
        if (is_int($callback)) {
            return $this->assertDispatchedTimes($event, $callback);
        }

        PHPUnit::assertTrue(
            $this->dispatched($event, $callback)->count() > 0,
            "The expected [{$event}] event was not dispatched."
        );
    }

    /**
     * Assert if a event was dispatched a number of times.
	 * 断言是否事件被多次调度
     *
     * @param  string  $event
     * @param  int  $times
     * @return void
     */
    public function assertDispatchedTimes($event, $times = 1)
    {
        PHPUnit::assertTrue(
            ($count = $this->dispatched($event)->count()) === $times,
            "The expected [{$event}] event was dispatched {$count} times instead of {$times} times."
        );
    }

    /**
     * Determine if an event was dispatched based on a truth-test callback.
	 * 确定是否根据真值测试回调分派事件
     *
     * @param  string  $event
     * @param  callable|null  $callback
     * @return void
     */
    public function assertNotDispatched($event, $callback = null)
    {
        PHPUnit::assertTrue(
            $this->dispatched($event, $callback)->count() === 0,
            "The unexpected [{$event}] event was dispatched."
        );
    }

    /**
     * Get all of the events matching a truth-test callback.
	 * 得到与true-test回调匹配的所有事件
     *
     * @param  string  $event
     * @param  callable|null  $callback
     * @return \Illuminate\Support\Collection
     */
    public function dispatched($event, $callback = null)
    {
        if (! $this->hasDispatched($event)) {
            return collect();
        }

        $callback = $callback ?: function () {
            return true;
        };

        return collect($this->events[$event])->filter(function ($arguments) use ($callback) {
            return $callback(...$arguments);
        });
    }

    /**
     * Determine if the given event has been dispatched.
	 * 确定是否已分派给定的事件
     *
     * @param  string  $event
     * @return bool
     */
    public function hasDispatched($event)
    {
        return isset($this->events[$event]) && ! empty($this->events[$event]);
    }

    /**
     * Register an event listener with the dispatcher.
	 * 注册事件侦听器向调度程序
     *
     * @param  string|array  $events
     * @param  mixed  $listener
     * @return void
     */
    public function listen($events, $listener)
    {
        $this->dispatcher->listen($events, $listener);
    }

    /**
     * Determine if a given event has listeners.
	 * 确定给定事件是否有侦听器
     *
     * @param  string  $eventName
     * @return bool
     */
    public function hasListeners($eventName)
    {
        return $this->dispatcher->hasListeners($eventName);
    }

    /**
     * Register an event and payload to be dispatched later.
	 * 注册以后要分派的事件和有效负载
     *
     * @param  string  $event
     * @param  array  $payload
     * @return void
     */
    public function push($event, $payload = [])
    {
        //
    }

    /**
     * Register an event subscriber with the dispatcher.
	 * 向调度程序注册事件订阅者
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
	 * 刷新一组推送的事件
     *
     * @param  string  $event
     * @return void
     */
    public function flush($event)
    {
        //
    }

    /**
     * Fire an event and call the listeners.
	 * 触发一个事件并调用侦听器
     *
     * @param  string|object  $event
     * @param  mixed  $payload
     * @param  bool  $halt
     * @return array|null
     */
    public function dispatch($event, $payload = [], $halt = false)
    {
        $name = is_object($event) ? get_class($event) : (string) $event;

        if ($this->shouldFakeEvent($name, $payload)) {
            $this->events[$name][] = func_get_args();
        } else {
            return $this->dispatcher->dispatch($event, $payload, $halt);
        }
    }

    /**
     * Determine if an event should be faked or actually dispatched.
	 * 确定是否应该伪造或实际分派事件
     *
     * @param  string  $eventName
     * @param  mixed  $payload
     * @return bool
     */
    protected function shouldFakeEvent($eventName, $payload)
    {
        if (empty($this->eventsToFake)) {
            return true;
        }

        return collect($this->eventsToFake)
            ->filter(function ($event) use ($eventName, $payload) {
                return $event instanceof Closure
                            ? $event($eventName, $payload)
                            : $event === $eventName;
            })
            ->isNotEmpty();
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
        //
    }

    /**
     * Forget all of the queued listeners.
	 * 忘记所有排队的侦听器
     *
     * @return void
     */
    public function forgetPushed()
    {
        //
    }

    /**
     * Dispatch an event and call the listeners.
	 * 分派事件并调用侦听器
     *
     * @param  string|object  $event
     * @param  mixed  $payload
     * @return void
     */
    public function until($event, $payload = [])
    {
        return $this->dispatch($event, $payload, true);
    }
}

<?php
/**
 * Redis，连接抽象类
 */

namespace Illuminate\Redis\Connections;

use Closure;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Redis\Events\CommandExecuted;
use Illuminate\Redis\Limiters\ConcurrencyLimiterBuilder;
use Illuminate\Redis\Limiters\DurationLimiterBuilder;
use Illuminate\Support\Traits\Macroable;

abstract class Connection
{
    use Macroable {
        __call as macroCall;
    }

    /**
     * The Redis client.
	 * Redis客户端
     *
     * @var \Redis
     */
    protected $client;

    /**
     * The Redis connection name.
	 * Redis连接名
     *
     * @var string|null
     */
    protected $name;

    /**
     * The event dispatcher instance.
	 * 事件调度实例
     *
     * @var \Illuminate\Contracts\Events\Dispatcher
     */
    protected $events;

    /**
     * Subscribe to a set of given channels for messages.
	 * 订阅一组给定的通道为消息
     *
     * @param  array|string  $channels
     * @param  \Closure  $callback
     * @param  string  $method
     * @return void
     */
    abstract public function createSubscription($channels, Closure $callback, $method = 'subscribe');

    /**
     * Funnel a callback for a maximum number of simultaneous executions.
	 * 设置一个漏斗回调为同时执行的最大数量
     *
     * @param  string  $name
     * @return \Illuminate\Redis\Limiters\ConcurrencyLimiterBuilder
     */
    public function funnel($name)
    {
        return new ConcurrencyLimiterBuilder($this, $name);
    }

    /**
     * Throttle a callback for a maximum number of executions over a given duration.
	 * 限制回调的最大执行次数在给定的持续时间内
     *
     * @param  string  $name
     * @return \Illuminate\Redis\Limiters\DurationLimiterBuilder
     */
    public function throttle($name)
    {
        return new DurationLimiterBuilder($this, $name);
    }

    /**
     * Get the underlying Redis client.
	 * 得到底层Redis客户端
     *
     * @return mixed
     */
    public function client()
    {
        return $this->client;
    }

    /**
     * Subscribe to a set of given channels for messages.
	 * 订阅一组给定的通道为消息
     *
     * @param  array|string  $channels
     * @param  \Closure  $callback
     * @return void
     */
    public function subscribe($channels, Closure $callback)
    {
        return $this->createSubscription($channels, $callback, __FUNCTION__);
    }

    /**
     * Subscribe to a set of given channels with wildcards.
	 * 订阅一组给定的通道使用通配符
     *
     * @param  array|string  $channels
     * @param  \Closure  $callback
     * @return void
     */
    public function psubscribe($channels, Closure $callback)
    {
        return $this->createSubscription($channels, $callback, __FUNCTION__);
    }

    /**
     * Run a command against the Redis database.
	 * 运行命令对Redis数据库
     *
     * @param  string  $method
     * @param  array  $parameters
     * @return mixed
     */
    public function command($method, array $parameters = [])
    {
        $start = microtime(true);

        $result = $this->client->{$method}(...$parameters);

        $time = round((microtime(true) - $start) * 1000, 2);

        if (isset($this->events)) {
            $this->event(new CommandExecuted($method, $parameters, $time, $this));
        }

        return $result;
    }

    /**
     * Fire the given event if possible.
	 * 触发给定的事件如果可能
     *
     * @param  mixed  $event
     * @return void
     */
    protected function event($event)
    {
        if (isset($this->events)) {
            $this->events->dispatch($event);
        }
    }

    /**
     * Register a Redis command listener with the connection.
	 * 注册一个Redis命令监听器在连接中
     *
     * @param  \Closure  $callback
     * @return void
     */
    public function listen(Closure $callback)
    {
        if (isset($this->events)) {
            $this->events->listen(CommandExecuted::class, $callback);
        }
    }

    /**
     * Get the connection name.
	 * 得到连接名
     *
     * @return string|null
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Set the connections name.
	 * 设置连接名
     *
     * @param  string  $name
     * @return $this
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Get the event dispatcher used by the connection.
	 * 得到连接使用的事件调度程序
     *
     * @return \Illuminate\Contracts\Events\Dispatcher
     */
    public function getEventDispatcher()
    {
        return $this->events;
    }

    /**
     * Set the event dispatcher instance on the connection.
	 * 设置事件调度程序实例在连接上
     *
     * @param  \Illuminate\Contracts\Events\Dispatcher  $events
     * @return void
     */
    public function setEventDispatcher(Dispatcher $events)
    {
        $this->events = $events;
    }

    /**
     * Unset the event dispatcher instance on the connection.
	 * 取消连接上的事件调度程序实例的设置
     *
     * @return void
     */
    public function unsetEventDispatcher()
    {
        $this->events = null;
    }

    /**
     * Pass other method calls down to the underlying client.
	 * 传递其他方法调用给底层客户端
     *
     * @param  string  $method
     * @param  array  $parameters
     * @return mixed
     */
    public function __call($method, $parameters)
    {
        if (static::hasMacro($method)) {
            return $this->macroCall($method, $parameters);
        }

        return $this->command($method, $parameters);
    }
}

<?php
/**
 * 支持，门面事件
 */

namespace Illuminate\Support\Facades;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Testing\Fakes\EventFake;

/**
 * @method static void listen(string|array $events, \Closure|string $listener)
 * @method static bool hasListeners(string $eventName)
 * @method static void push(string $event, array $payload = [])
 * @method static void flush(string $event)
 * @method static void subscribe(object|string $subscriber)
 * @method static array|null until(string|object $event, mixed $payload = [])
 * @method static array|null dispatch(string|object $event, mixed $payload = [], bool $halt = false)
 * @method static array getListeners(string $eventName)
 * @method static \Closure makeListener(\Closure|string $listener, bool $wildcard = false)
 * @method static \Closure createClassListener(string $listener, bool $wildcard = false)
 * @method static void forget(string $event)
 * @method static void forgetPushed()
 * @method static \Illuminate\Events\Dispatcher setQueueResolver(callable $resolver)
 * @method static void assertDispatched(string $event, callable|int $callback = null)
 * @method static void assertDispatchedTimes(string $event, int $times = 1)
 * @method static void assertNotDispatched(string $event, callable|int $callback = null)
 *
 * @see \Illuminate\Events\Dispatcher
 */
class Event extends Facade
{
    /**
     * Replace the bound instance with a fake.
	 * 替换绑定实例为假实例
     *
     * @param  array|string  $eventsToFake
     * @return \Illuminate\Support\Testing\Fakes\EventFake
     */
    public static function fake($eventsToFake = [])
    {
        static::swap($fake = new EventFake(static::getFacadeRoot(), $eventsToFake));

        Model::setEventDispatcher($fake);
        Cache::refreshEventDispatcher();

        return $fake;
    }

    /**
     * Replace the bound instance with a fake during the given callable's execution.
	 * 在给定的可调用对象执行期间，将绑定实例替换为假实例。
     *
     * @param  callable  $callable
     * @param  array  $eventsToFake
     * @return callable
     */
    public static function fakeFor(callable $callable, array $eventsToFake = [])
    {
        $originalDispatcher = static::getFacadeRoot();

        static::fake($eventsToFake);

        return tap($callable(), function () use ($originalDispatcher) {
            static::swap($originalDispatcher);

            Model::setEventDispatcher($originalDispatcher);
            Cache::refreshEventDispatcher();
        });
    }

    /**
     * Get the registered name of the component.
	 * 得到组件注册名
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return 'events';
    }
}

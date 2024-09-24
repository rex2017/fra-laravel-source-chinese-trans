<?php
/**
 * 支持，门面通知
 */

namespace Illuminate\Support\Facades;

use Illuminate\Notifications\AnonymousNotifiable;
use Illuminate\Notifications\ChannelManager;
use Illuminate\Support\Testing\Fakes\NotificationFake;

/**
 * @method static void send(\Illuminate\Support\Collection|array|mixed $notifiables, $notification)
 * @method static void sendNow(\Illuminate\Support\Collection|array|mixed $notifiables, $notification)
 * @method static mixed channel(string|null $name = null)
 * @method static \Illuminate\Notifications\ChannelManager locale(string|null $locale)
 * @method static void assertSentTo(mixed $notifiable, string $notification, callable $callback = null)
 * @method static void assertSentToTimes(mixed $notifiable, string $notification, int $times = 1)
 * @method static void assertNotSentTo(mixed $notifiable, string $notification, callable $callback = null)
 * @method static void assertNothingSent()
 * @method static void assertTimesSent(int $expectedCount, string $notification)
 * @method static \Illuminate\Support\Collection sent(mixed $notifiable, string $notification, callable $callback = null)
 * @method static bool hasSent(mixed $notifiable, string $notification)
 *
 * @see \Illuminate\Notifications\ChannelManager
 */
class Notification extends Facade
{
    /**
     * Replace the bound instance with a fake.
	 * 替换绑定实例为伪实例
     *
     * @return \Illuminate\Support\Testing\Fakes\NotificationFake
     */
    public static function fake()
    {
        static::swap($fake = new NotificationFake);

        return $fake;
    }

    /**
     * Begin sending a notification to an anonymous notifiable.
	 * 开始向匿名通知对象发送通知
     *
     * @param  string  $channel
     * @param  mixed  $route
     * @return \Illuminate\Notifications\AnonymousNotifiable
     */
    public static function route($channel, $route)
    {
        return (new AnonymousNotifiable)->route($channel, $route);
    }

    /**
     * Get the registered name of the component.
	 * 得到组件注册名
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return ChannelManager::class;
    }
}

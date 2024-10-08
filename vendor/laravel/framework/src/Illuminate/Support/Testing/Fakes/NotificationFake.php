<?php
/**
 * 支持，通知伪造
 */

namespace Illuminate\Support\Testing\Fakes;

use Exception;
use Illuminate\Contracts\Notifications\Dispatcher as NotificationDispatcher;
use Illuminate\Contracts\Notifications\Factory as NotificationFactory;
use Illuminate\Contracts\Translation\HasLocalePreference;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Illuminate\Support\Traits\Macroable;
use PHPUnit\Framework\Assert as PHPUnit;

class NotificationFake implements NotificationDispatcher, NotificationFactory
{
    use Macroable;

    /**
     * All of the notifications that have been sent.
	 * 所有已发送的通知
     *
     * @var array
     */
    protected $notifications = [];

    /**
     * Locale used when sending notifications.
	 * 发送通知时使用的区域设置
     *
     * @var string|null
     */
    public $locale;

    /**
     * Assert if a notification was sent based on a truth-test callback.
	 * 判断通知是否基于真值测试回调发送
     *
     * @param  mixed  $notifiable
     * @param  string  $notification
     * @param  callable|null  $callback
     * @return void
     *
     * @throws \Exception
     */
    public function assertSentTo($notifiable, $notification, $callback = null)
    {
        if (is_array($notifiable) || $notifiable instanceof Collection) {
            if (count($notifiable) === 0) {
                throw new Exception('No notifiable given.');
            }

            foreach ($notifiable as $singleNotifiable) {
                $this->assertSentTo($singleNotifiable, $notification, $callback);
            }

            return;
        }

        if (is_numeric($callback)) {
            return $this->assertSentToTimes($notifiable, $notification, $callback);
        }

        PHPUnit::assertTrue(
            $this->sent($notifiable, $notification, $callback)->count() > 0,
            "The expected [{$notification}] notification was not sent."
        );
    }

    /**
     * Assert if a notification was sent a number of times.
	 * 判断是否发送了多次通知
     *
     * @param  mixed  $notifiable
     * @param  string  $notification
     * @param  int  $times
     * @return void
     */
    public function assertSentToTimes($notifiable, $notification, $times = 1)
    {
        PHPUnit::assertTrue(
            ($count = $this->sent($notifiable, $notification)->count()) === $times,
            "Expected [{$notification}] to be sent {$times} times, but was sent {$count} times."
        );
    }

    /**
     * Determine if a notification was sent based on a truth-test callback.
	 * 确定是否根据真值测试回调发送了通知
     *
     * @param  mixed  $notifiable
     * @param  string  $notification
     * @param  callable|null  $callback
     * @return void
     *
     * @throws \Exception
     */
    public function assertNotSentTo($notifiable, $notification, $callback = null)
    {
        if (is_array($notifiable) || $notifiable instanceof Collection) {
            if (count($notifiable) === 0) {
                throw new Exception('No notifiable given.');
            }

            foreach ($notifiable as $singleNotifiable) {
                $this->assertNotSentTo($singleNotifiable, $notification, $callback);
            }

            return;
        }

        PHPUnit::assertTrue(
            $this->sent($notifiable, $notification, $callback)->count() === 0,
            "The unexpected [{$notification}] notification was sent."
        );
    }

    /**
     * Assert that no notifications were sent.
	 * 断言没有发送通知
     *
     * @return void
     */
    public function assertNothingSent()
    {
        PHPUnit::assertEmpty($this->notifications, 'Notifications were sent unexpectedly.');
    }

    /**
     * Assert the total amount of times a notification was sent.
	 * 断言发送通知的总次数
     *
     * @param  int  $expectedCount
     * @param  string  $notification
     * @return void
     */
    public function assertTimesSent($expectedCount, $notification)
    {
        $actualCount = collect($this->notifications)
            ->flatten(1)
            ->reduce(function ($count, $sent) use ($notification) {
                return $count + count($sent[$notification] ?? []);
            }, 0);

        PHPUnit::assertSame(
            $expectedCount, $actualCount,
            "Expected [{$notification}] to be sent {$expectedCount} times, but was sent {$actualCount} times."
        );
    }

    /**
     * Get all of the notifications matching a truth-test callback.
	 * 得到所有与true-test回调匹配的通知
     *
     * @param  mixed  $notifiable
     * @param  string  $notification
     * @param  callable|null  $callback
     * @return \Illuminate\Support\Collection
     */
    public function sent($notifiable, $notification, $callback = null)
    {
        if (! $this->hasSent($notifiable, $notification)) {
            return collect();
        }

        $callback = $callback ?: function () {
            return true;
        };

        $notifications = collect($this->notificationsFor($notifiable, $notification));

        return $notifications->filter(function ($arguments) use ($callback) {
            return $callback(...array_values($arguments));
        })->pluck('notification');
    }

    /**
     * Determine if there are more notifications left to inspect.
	 * 确定是否还有更多通知需要检查
     *
     * @param  mixed  $notifiable
     * @param  string  $notification
     * @return bool
     */
    public function hasSent($notifiable, $notification)
    {
        return ! empty($this->notificationsFor($notifiable, $notification));
    }

    /**
     * Get all of the notifications for a notifiable entity by type.
	 * 按类型得到可通知实体的所有通知
     *
     * @param  mixed  $notifiable
     * @param  string  $notification
     * @return array
     */
    protected function notificationsFor($notifiable, $notification)
    {
        return $this->notifications[get_class($notifiable)][$notifiable->getKey()][$notification] ?? [];
    }

    /**
     * Send the given notification to the given notifiable entities.
	 * 发送到给定的可通知实体将给定的通知
     *
     * @param  \Illuminate\Support\Collection|array|mixed  $notifiables
     * @param  mixed  $notification
     * @return void
     */
    public function send($notifiables, $notification)
    {
        return $this->sendNow($notifiables, $notification);
    }

    /**
     * Send the given notification immediately.
	 * 立即发送给定的通知
     *
     * @param  \Illuminate\Support\Collection|array|mixed  $notifiables
     * @param  mixed  $notification
     * @param  array|null  $channels
     * @return void
     */
    public function sendNow($notifiables, $notification, array $channels = null)
    {
        if (! $notifiables instanceof Collection && ! is_array($notifiables)) {
            $notifiables = [$notifiables];
        }

        foreach ($notifiables as $notifiable) {
            if (! $notification->id) {
                $notification->id = Str::uuid()->toString();
            }

            $this->notifications[get_class($notifiable)][$notifiable->getKey()][get_class($notification)][] = [
                'notification' => $notification,
                'channels' => $channels ?: $notification->via($notifiable),
                'notifiable' => $notifiable,
                'locale' => $notification->locale ?? $this->locale ?? value(function () use ($notifiable) {
                    if ($notifiable instanceof HasLocalePreference) {
                        return $notifiable->preferredLocale();
                    }
                }),
            ];
        }
    }

    /**
     * Get a channel instance by name.
	 * 得到通道实例按名称
     *
     * @param  string|null  $name
     * @return mixed
     */
    public function channel($name = null)
    {
        //
    }

    /**
     * Set the locale of notifications.
	 * 设置通知的区域设置
     *
     * @param  string  $locale
     * @return $this
     */
    public function locale($locale)
    {
        $this->locale = $locale;

        return $this;
    }
}

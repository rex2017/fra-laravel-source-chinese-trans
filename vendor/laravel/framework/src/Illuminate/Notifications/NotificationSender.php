<?php
/**
 * 通知发送人
 */

namespace Illuminate\Notifications;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Contracts\Translation\HasLocalePreference;
use Illuminate\Database\Eloquent\Collection as ModelCollection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Events\NotificationSending;
use Illuminate\Notifications\Events\NotificationSent;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Illuminate\Support\Traits\Localizable;

class NotificationSender
{
    use Localizable;

    /**
     * The notification manager instance.
	 * 通知管理器实例
     *
     * @var \Illuminate\Notifications\ChannelManager
     */
    protected $manager;

    /**
     * The Bus dispatcher instance.
	 * 总线调度程序实例
     *
     * @var \Illuminate\Contracts\Bus\Dispatcher
     */
    protected $bus;

    /**
     * The event dispatcher.
	 * 事件调度器
     *
     * @var \Illuminate\Contracts\Events\Dispatcher
     */
    protected $events;

    /**
     * The locale to be used when sending notifications.
	 * 发送通知时要使用的区域设置
     *
     * @var string|null
     */
    protected $locale;

    /**
     * Create a new notification sender instance.
	 * 创建新的通知发送方实例
     *
     * @param  \Illuminate\Notifications\ChannelManager  $manager
     * @param  \Illuminate\Contracts\Bus\Dispatcher  $bus
     * @param  \Illuminate\Contracts\Events\Dispatcher  $events
     * @param  string|null  $locale
     * @return void
     */
    public function __construct($manager, $bus, $events, $locale = null)
    {
        $this->bus = $bus;
        $this->events = $events;
        $this->locale = $locale;
        $this->manager = $manager;
    }

    /**
     * Send the given notification to the given notifiable entities.
	 * 发送给定的通知到给定的可通知实体
     *
     * @param  \Illuminate\Support\Collection|array|mixed  $notifiables
     * @param  mixed  $notification
     * @return void
     */
    public function send($notifiables, $notification)
    {
        $notifiables = $this->formatNotifiables($notifiables);

        if ($notification instanceof ShouldQueue) {
            return $this->queueNotification($notifiables, $notification);
        }

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
        $notifiables = $this->formatNotifiables($notifiables);

        $original = clone $notification;

        foreach ($notifiables as $notifiable) {
            if (empty($viaChannels = $channels ?: $notification->via($notifiable))) {
                continue;
            }

            $this->withLocale($this->preferredLocale($notifiable, $notification), function () use ($viaChannels, $notifiable, $original) {
                $notificationId = Str::uuid()->toString();

                foreach ((array) $viaChannels as $channel) {
                    if (! ($notifiable instanceof AnonymousNotifiable && $channel === 'database')) {
                        $this->sendToNotifiable($notifiable, $notificationId, clone $original, $channel);
                    }
                }
            });
        }
    }

    /**
     * Get the notifiable's preferred locale for the notification.
	 * 得到通知的被通知对象的首选语言环境
     *
     * @param  mixed  $notifiable
     * @param  mixed  $notification
     * @return string|null
     */
    protected function preferredLocale($notifiable, $notification)
    {
        return $notification->locale ?? $this->locale ?? value(function () use ($notifiable) {
            if ($notifiable instanceof HasLocalePreference) {
                return $notifiable->preferredLocale();
            }
        });
    }

    /**
     * Send the given notification to the given notifiable via a channel.
	 * 发送给定的通知给给定的通知对象通过通道。
     *
     * @param  mixed  $notifiable
     * @param  string  $id
     * @param  mixed  $notification
     * @param  string  $channel
     * @return void
     */
    protected function sendToNotifiable($notifiable, $id, $notification, $channel)
    {
        if (! $notification->id) {
            $notification->id = $id;
        }

        if (! $this->shouldSendNotification($notifiable, $notification, $channel)) {
            return;
        }

        $response = $this->manager->driver($channel)->send($notifiable, $notification);

        $this->events->dispatch(
            new NotificationSent($notifiable, $notification, $channel, $response)
        );
    }

    /**
     * Determines if the notification can be sent.
	 * 确定是否可以发送通知
     *
     * @param  mixed  $notifiable
     * @param  mixed  $notification
     * @param  string  $channel
     * @return bool
     */
    protected function shouldSendNotification($notifiable, $notification, $channel)
    {
        return $this->events->until(
            new NotificationSending($notifiable, $notification, $channel)
        ) !== false;
    }

    /**
     * Queue the given notification instances.
	 * 排队给定的通知实例
     *
     * @param  mixed  $notifiables
     * @param  \Illuminate\Notifications\Notification  $notification
     * @return void
     */
    protected function queueNotification($notifiables, $notification)
    {
        $notifiables = $this->formatNotifiables($notifiables);

        $original = clone $notification;

        foreach ($notifiables as $notifiable) {
            $notificationId = Str::uuid()->toString();

            foreach ((array) $original->via($notifiable) as $channel) {
                $notification = clone $original;

                $notification->id = $notificationId;

                if (! is_null($this->locale)) {
                    $notification->locale = $this->locale;
                }

                $this->bus->dispatch(
                    (new SendQueuedNotifications($notifiable, $notification, [$channel]))
                            ->onConnection($notification->connection)
                            ->onQueue($notification->queue)
                            ->delay($notification->delay)
                            ->through(
                                array_merge(
                                    method_exists($notification, 'middleware') ? $notification->middleware() : [],
                                    $notification->middleware ?? []
                                )
                            )
                );
            }
        }
    }

    /**
     * Format the notifiables into a Collection / array if necessary.
	 * 格式化可通知对象为集合/数组如有必要
     *
     * @param  mixed  $notifiables
     * @return \Illuminate\Database\Eloquent\Collection|array
     */
    protected function formatNotifiables($notifiables)
    {
        if (! $notifiables instanceof Collection && ! is_array($notifiables)) {
            return $notifiables instanceof Model
                            ? new ModelCollection([$notifiables]) : [$notifiables];
        }

        return $notifiables;
    }
}

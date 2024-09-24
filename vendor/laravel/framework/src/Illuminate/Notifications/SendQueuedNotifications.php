<?php
/**
 * 发送队列通知
 */

namespace Illuminate\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SendQueuedNotifications implements ShouldQueue
{
    use InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The notifiable entities that should receive the notification.
	 * 应接收通知的应通知实体
     *
     * @var \Illuminate\Support\Collection
     */
    public $notifiables;

    /**
     * The notification to be sent.
	 * 要发送的通知
     *
     * @var \Illuminate\Notifications\Notification
     */
    public $notification;

    /**
     * All of the channels to send the notification to.
	 * 将通知发送到的所有通道
     *
     * @var array
     */
    public $channels;

    /**
     * The number of times the job may be attempted.
	 * 可能尝试该作业的次数
     *
     * @var int
     */
    public $tries;

    /**
     * The number of seconds the job can run before timing out.
	 * 作业在超时之前可以运行的秒数
     *
     * @var int
     */
    public $timeout;

    /**
     * Create a new job instance.
	 * 创建新的作业实例
     *
     * @param  \Illuminate\Support\Collection  $notifiables
     * @param  \Illuminate\Notifications\Notification  $notification
     * @param  array|null  $channels
     * @return void
     */
    public function __construct($notifiables, $notification, array $channels = null)
    {
        $this->channels = $channels;
        $this->notifiables = $notifiables;
        $this->notification = $notification;
        $this->tries = property_exists($notification, 'tries') ? $notification->tries : null;
        $this->timeout = property_exists($notification, 'timeout') ? $notification->timeout : null;
    }

    /**
     * Send the notifications.
	 * 发送通知
     *
     * @param  \Illuminate\Notifications\ChannelManager  $manager
     * @return void
     */
    public function handle(ChannelManager $manager)
    {
        $manager->sendNow($this->notifiables, $this->notification, $this->channels);
    }

    /**
     * Get the display name for the queued job.
	 * 得到排队作业的显示名称
     *
     * @return string
     */
    public function displayName()
    {
        return get_class($this->notification);
    }

    /**
     * Call the failed method on the notification instance.
	 * 调用失败的方法在通知实例上
     *
     * @param  \Exception  $e
     * @return void
     */
    public function failed($e)
    {
        if (method_exists($this->notification, 'failed')) {
            $this->notification->failed($e);
        }
    }

    /**
     * Get the retry delay for the notification.
	 * 得到通知的重试延迟 
     *
     * @return mixed
     */
    public function retryAfter()
    {
        if (! method_exists($this->notification, 'retryAfter') && ! isset($this->notification->retryAfter)) {
            return;
        }

        return $this->notification->retryAfter ?? $this->notification->retryAfter();
    }

    /**
     * Get the expiration for the notification.
	 * 得到通知的过期时间
     *
     * @return mixed
     */
    public function retryUntil()
    {
        if (! method_exists($this->notification, 'retryUntil') && ! isset($this->notification->timeoutAt)) {
            return;
        }

        return $this->notification->timeoutAt ?? $this->notification->retryUntil();
    }

    /**
     * Prepare the instance for cloning.
	 * 为克隆准备实例
     *
     * @return void
     */
    public function __clone()
    {
        $this->notifiables = clone $this->notifiables;
        $this->notification = clone $this->notification;
    }
}

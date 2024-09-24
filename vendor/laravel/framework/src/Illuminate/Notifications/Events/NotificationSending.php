<?php
/**
 * 通知，通知发送中
 */

namespace Illuminate\Notifications\Events;

use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;

class NotificationSending
{
    use Queueable, SerializesModels;

    /**
     * The notifiable entity who received the notification.
	 * 通知实体应收到通知的
     *
     * @var mixed
     */
    public $notifiable;

    /**
     * The notification instance.
	 * 通知实例
     *
     * @var \Illuminate\Notifications\Notification
     */
    public $notification;

    /**
     * The channel name.
	 * 通道名称
     *
     * @var string
     */
    public $channel;

    /**
     * Create a new event instance.
	 * 创建新的事件实例
     *
     * @param  mixed  $notifiable
     * @param  \Illuminate\Notifications\Notification  $notification
     * @param  string  $channel
     * @return void
     */
    public function __construct($notifiable, $notification, $channel)
    {
        $this->channel = $channel;
        $this->notifiable = $notifiable;
        $this->notification = $notification;
    }
}

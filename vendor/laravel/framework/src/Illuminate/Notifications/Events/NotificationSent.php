<?php
/**
 * 通知，通知发送
 */

namespace Illuminate\Notifications\Events;

use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;

class NotificationSent
{
    use Queueable, SerializesModels;

    /**
     * The notifiable entity who received the notification.
	 * 通知实例应收到通知的
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
     * The channel's response.
	 * 通道响应
     *
     * @var mixed
     */
    public $response;

    /**
     * Create a new event instance.
	 * 创建新的事件实例
     *
     * @param  mixed  $notifiable
     * @param  \Illuminate\Notifications\Notification  $notification
     * @param  string  $channel
     * @param  mixed  $response
     * @return void
     */
    public function __construct($notifiable, $notification, $channel, $response = null)
    {
        $this->channel = $channel;
        $this->response = $response;
        $this->notifiable = $notifiable;
        $this->notification = $notification;
    }
}

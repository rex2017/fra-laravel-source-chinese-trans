<?php
/**
 * 通知，核心类
 */

namespace Illuminate\Notifications;

use Illuminate\Queue\SerializesModels;

class Notification
{
    use SerializesModels;

    /**
     * The unique identifier for the notification.
	 * 通知的唯一标识符
     *
     * @var string
     */
    public $id;

    /**
     * The locale to be used when sending the notification.
	 * 发送通知时要使用的区域设置
     *
     * @var string|null
     */
    public $locale;

    /**
     * Get the channels the event should broadcast on.
	 * 得到该事件应该播放的频道
     *
     * @return array
     */
    public function broadcastOn()
    {
        return [];
    }

    /**
     * Set the locale to send this notification in.
	 * 设置发送此通知的区域设置
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

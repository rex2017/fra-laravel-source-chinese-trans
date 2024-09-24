<?php
/**
 * 通知，广播通知创建
 */

namespace Illuminate\Notifications\Events;

use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Queue\SerializesModels;

class BroadcastNotificationCreated implements ShouldBroadcast
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
     * The notification data.
	 * 通知数据
     *
     * @var array
     */
    public $data = [];

    /**
     * Create a new event instance.
	 * 创建新的事件实例
     *
     * @param  mixed  $notifiable
     * @param  \Illuminate\Notifications\Notification  $notification
     * @param  array  $data
     * @return void
     */
    public function __construct($notifiable, $notification, $data)
    {
        $this->data = $data;
        $this->notifiable = $notifiable;
        $this->notification = $notification;
    }

    /**
     * Get the channels the event should broadcast on.
	 * 得到该事件应该播放的通道
     *
     * @return array
     */
    public function broadcastOn()
    {
        $channels = $this->notification->broadcastOn();

        if (! empty($channels)) {
            return $channels;
        }

        if (is_string($channels = $this->channelName())) {
            return [new PrivateChannel($channels)];
        }

        return collect($channels)->map(function ($channel) {
            return new PrivateChannel($channel);
        })->all();
    }

    /**
     * Get the broadcast channel name for the event.
	 * 得到事件的广播频道名称
     *
     * @return array|string
     */
    protected function channelName()
    {
        if (method_exists($this->notifiable, 'receivesBroadcastNotificationsOn')) {
            return $this->notifiable->receivesBroadcastNotificationsOn($this->notification);
        }

        $class = str_replace('\\', '.', get_class($this->notifiable));

        return $class.'.'.$this->notifiable->getKey();
    }

    /**
     * Get the data that should be sent with the broadcasted event.
	 * 得到应该随广播事件一起发送的数据
     *
     * @return array
     */
    public function broadcastWith()
    {
        return array_merge($this->data, [
            'id' => $this->notification->id,
            'type' => $this->broadcastType(),
        ]);
    }

    /**
     * Get the type of the notification being broadcast.
	 * 得到正在广播的通知的类型
     *
     * @return string
     */
    public function broadcastType()
    {
        return method_exists($this->notification, 'broadcastType')
                    ? $this->notification->broadcastType()
                    : get_class($this->notification);
    }
}

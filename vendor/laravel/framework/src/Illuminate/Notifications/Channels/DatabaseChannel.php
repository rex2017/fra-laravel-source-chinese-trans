<?php
/**
 * 通知，数据库通道
 */

namespace Illuminate\Notifications\Channels;

use Illuminate\Notifications\Notification;
use RuntimeException;

class DatabaseChannel
{
    /**
     * Send the given notification.
	 * 发送给定的通知
     *
     * @param  mixed  $notifiable
     * @param  \Illuminate\Notifications\Notification  $notification
     * @return \Illuminate\Database\Eloquent\Model
     */
    public function send($notifiable, Notification $notification)
    {
        return $notifiable->routeNotificationFor('database', $notification)->create(
            $this->buildPayload($notifiable, $notification)
        );
    }

    /**
     * Get the data for the notification.
	 * 得到通知数据
     *
     * @param  mixed  $notifiable
     * @param  \Illuminate\Notifications\Notification  $notification
     * @return array
     *
     * @throws \RuntimeException
     */
    protected function getData($notifiable, Notification $notification)
    {
        if (method_exists($notification, 'toDatabase')) {
            return is_array($data = $notification->toDatabase($notifiable))
                                ? $data : $data->data;
        }

        if (method_exists($notification, 'toArray')) {
            return $notification->toArray($notifiable);
        }

        throw new RuntimeException('Notification is missing toDatabase / toArray method.');
    }

    /**
     * Build an array payload for the DatabaseNotification Model.
	 * 构建一个数组有效负载
     *
     * @param  mixed  $notifiable
     * @param  \Illuminate\Notifications\Notification  $notification
     * @return array
     */
    protected function buildPayload($notifiable, Notification $notification)
    {
        return [
            'id' => $notification->id,
            'type' => get_class($notification),
            'data' => $this->getData($notifiable, $notification),
            'read_at' => null,
        ];
    }
}

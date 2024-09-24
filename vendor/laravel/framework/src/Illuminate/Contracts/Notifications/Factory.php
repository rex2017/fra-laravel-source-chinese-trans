<?php
/**
 * 契约，通知工厂接口
 */

namespace Illuminate\Contracts\Notifications;

interface Factory
{
    /**
     * Get a channel instance by name.
	 * 得到通道实例
     *
     * @param  string|null  $name
     * @return mixed
     */
    public function channel($name = null);

    /**
     * Send the given notification to the given notifiable entities.
	 * 发送通知给给定的实体
     *
     * @param  \Illuminate\Support\Collection|array|mixed  $notifiables
     * @param  mixed  $notification
     * @return void
     */
    public function send($notifiables, $notification);

    /**
     * Send the given notification immediately.
	 * 发送通知立即
     *
     * @param  \Illuminate\Support\Collection|array|mixed  $notifiables
     * @param  mixed  $notification
     * @return void
     */
    public function sendNow($notifiables, $notification);
}

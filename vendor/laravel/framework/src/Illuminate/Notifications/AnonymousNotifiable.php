<?php
/**
 * 通知，匿名通知
 */

namespace Illuminate\Notifications;

use Illuminate\Contracts\Notifications\Dispatcher;
use InvalidArgumentException;

class AnonymousNotifiable
{
    /**
     * All of the notification routing information.
	 * 路由信息
     *
     * @var array
     */
    public $routes = [];

    /**
     * Add routing information to the target.
	 * 添加路由信息向目标
     *
     * @param  string  $channel
     * @param  mixed  $route
     * @return $this
     */
    public function route($channel, $route)
    {
        if ($channel === 'database') {
            throw new InvalidArgumentException('The database channel does not support on-demand notifications.');
        }

        $this->routes[$channel] = $route;

        return $this;
    }

    /**
     * Send the given notification.
	 * 发送给定通知
     *
     * @param  mixed  $notification
     * @return void
     */
    public function notify($notification)
    {
        app(Dispatcher::class)->send($this, $notification);
    }

    /**
     * Send the given notification immediately.
	 * 发送给定的通知
     *
     * @param  mixed  $notification
     * @return void
     */
    public function notifyNow($notification)
    {
        app(Dispatcher::class)->sendNow($this, $notification);
    }

    /**
     * Get the notification routing information for the given driver.
	 * 得到给定驱动程序的通知路由信息
     *
     * @param  string  $driver
     * @return mixed
     */
    public function routeNotificationFor($driver)
    {
        return $this->routes[$driver] ?? null;
    }

    /**
     * Get the value of the notifiable's primary key.
	 * 得到被通知对象的主键的值
     *
     * @return mixed
     */
    public function getKey()
    {
        //
    }
}

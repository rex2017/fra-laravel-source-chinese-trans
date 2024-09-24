<?php
/**
 * 通知，通道管理
 */

namespace Illuminate\Notifications;

use Illuminate\Contracts\Bus\Dispatcher as Bus;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Contracts\Notifications\Dispatcher as DispatcherContract;
use Illuminate\Contracts\Notifications\Factory as FactoryContract;
use Illuminate\Support\Manager;
use InvalidArgumentException;

class ChannelManager extends Manager implements DispatcherContract, FactoryContract
{
    /**
     * The default channel used to deliver messages.
	 * 用于传递消息的默认通道
     *
     * @var string
     */
    protected $defaultChannel = 'mail';

    /**
     * The locale used when sending notifications.
	 * 发送通知时使用的区域设置
     *
     * @var string|null
     */
    protected $locale;

    /**
     * Send the given notification to the given notifiable entities.
	 * 将给定的通知发送到给定的可通知实体
     *
     * @param  \Illuminate\Support\Collection|array|mixed  $notifiables
     * @param  mixed  $notification
     * @return void
     */
    public function send($notifiables, $notification)
    {
        return (new NotificationSender(
            $this, $this->container->make(Bus::class), $this->container->make(Dispatcher::class), $this->locale)
        )->send($notifiables, $notification);
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
        return (new NotificationSender(
            $this, $this->container->make(Bus::class), $this->container->make(Dispatcher::class), $this->locale)
        )->sendNow($notifiables, $notification, $channels);
    }

    /**
     * Get a channel instance.
	 * 得到通知实例
     *
     * @param  string|null  $name
     * @return mixed
     */
    public function channel($name = null)
    {
        return $this->driver($name);
    }

    /**
     * Create an instance of the database driver.
	 * 创建一个数据库驱动实例
     *
     * @return \Illuminate\Notifications\Channels\DatabaseChannel
     */
    protected function createDatabaseDriver()
    {
        return $this->container->make(Channels\DatabaseChannel::class);
    }

    /**
     * Create an instance of the broadcast driver.
	 * 创建一个广播驱动实例
     *
     * @return \Illuminate\Notifications\Channels\BroadcastChannel
     */
    protected function createBroadcastDriver()
    {
        return $this->container->make(Channels\BroadcastChannel::class);
    }

    /**
     * Create an instance of the mail driver.
	 * 创建邮件驱动程序的实例
     *
     * @return \Illuminate\Notifications\Channels\MailChannel
     */
    protected function createMailDriver()
    {
        return $this->container->make(Channels\MailChannel::class);
    }

    /**
     * Create a new driver instance.
	 * 创建新的的驱动实例
     *
     * @param  string  $driver
     * @return mixed
     *
     * @throws \InvalidArgumentException
     */
    protected function createDriver($driver)
    {
        try {
            return parent::createDriver($driver);
        } catch (InvalidArgumentException $e) {
            if (class_exists($driver)) {
                return $this->container->make($driver);
            }

            throw $e;
        }
    }

    /**
     * Get the default channel driver name.
	 * 得到默认通道驱动名
     *
     * @return string
     */
    public function getDefaultDriver()
    {
        return $this->defaultChannel;
    }

    /**
     * Get the default channel driver name.
	 * 得到默认通道驱动程序名称
     *
     * @return string
     */
    public function deliversVia()
    {
        return $this->getDefaultDriver();
    }

    /**
     * Set the default channel driver name.
	 * 设置默认通道驱动名称
     *
     * @param  string  $channel
     * @return void
     */
    public function deliverVia($channel)
    {
        $this->defaultChannel = $channel;
    }

    /**
     * Set the locale of notifications.
	 * 设置通知区域
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

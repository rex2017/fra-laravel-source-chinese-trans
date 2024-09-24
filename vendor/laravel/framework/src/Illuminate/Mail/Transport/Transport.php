<?php
/**
 * 邮件，传输抽象类
 */

namespace Illuminate\Mail\Transport;

use Swift_Events_EventListener;
use Swift_Events_SendEvent;
use Swift_Mime_SimpleMessage;
use Swift_Transport;

abstract class Transport implements Swift_Transport
{
    /**
     * The plug-ins registered with the transport.
	 * 使用传输注册的插件
     *
     * @var array
     */
    public $plugins = [];

    /**
     * {@inheritdoc}
     */
    public function isStarted()
    {
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function start()
    {
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function stop()
    {
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function ping()
    {
        return true;
    }

    /**
     * Register a plug-in with the transport.
	 * 向传输注册插件
     *
     * @param  \Swift_Events_EventListener  $plugin
     * @return void
     */
    public function registerPlugin(Swift_Events_EventListener $plugin)
    {
        array_push($this->plugins, $plugin);
    }

    /**
     * Iterate through registered plugins and execute plugins' methods.
	 * 遍历已注册的插件并执行插件的方法
     *
     * @param  \Swift_Mime_SimpleMessage  $message
     * @return void
     */
    protected function beforeSendPerformed(Swift_Mime_SimpleMessage $message)
    {
        $event = new Swift_Events_SendEvent($this, $message);

        foreach ($this->plugins as $plugin) {
            if (method_exists($plugin, 'beforeSendPerformed')) {
                $plugin->beforeSendPerformed($event);
            }
        }
    }

    /**
     * Iterate through registered plugins and execute plugins' methods.
	 * 遍历已注册的插件并执行插件的方法
     *
     * @param  \Swift_Mime_SimpleMessage  $message
     * @return void
     */
    protected function sendPerformed(Swift_Mime_SimpleMessage $message)
    {
        $event = new Swift_Events_SendEvent($this, $message);

        foreach ($this->plugins as $plugin) {
            if (method_exists($plugin, 'sendPerformed')) {
                $plugin->sendPerformed($event);
            }
        }
    }

    /**
     * Get the number of recipients.
	 * 得到收件人的数量
     *
     * @param  \Swift_Mime_SimpleMessage  $message
     * @return int
     */
    protected function numberOfRecipients(Swift_Mime_SimpleMessage $message)
    {
        return count(array_merge(
            (array) $message->getTo(), (array) $message->getCc(), (array) $message->getBcc()
        ));
    }
}

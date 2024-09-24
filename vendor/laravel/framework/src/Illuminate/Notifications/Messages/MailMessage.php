<?php
/**
 * 通知，邮件信息
 */

namespace Illuminate\Notifications\Messages;

use Illuminate\Container\Container;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Contracts\Support\Renderable;
use Illuminate\Mail\Markdown;
use Traversable;

class MailMessage extends SimpleMessage implements Renderable
{
    /**
     * The view to be rendered.
	 * 要呈现的视图
     *
     * @var array|string
     */
    public $view;

    /**
     * The view data for the message.
	 * 消息的视图数据
     *
     * @var array
     */
    public $viewData = [];

    /**
     * The Markdown template to render (if applicable).
	 * 要呈现的Markdown模板(如果适用)
     *
     * @var string|null
     */
    public $markdown = 'notifications::email';

    /**
     * The current theme being used when generating emails.
	 * 当前主题生成电子邮件时使用的
     *
     * @var string|null
     */
    public $theme;

    /**
     * The "from" information for the message.
	 * 消息的"from"信息
     *
     * @var array
     */
    public $from = [];

    /**
     * The "reply to" information for the message.
	 * 消息的"回复"信息
     *
     * @var array
     */
    public $replyTo = [];

    /**
     * The "cc" information for the message.
	 * 消息的"抄送"信息
     *
     * @var array
     */
    public $cc = [];

    /**
     * The "bcc" information for the message.
	 * 消息的"密送"信息
     *
     * @var array
     */
    public $bcc = [];

    /**
     * The attachments for the message.
	 * 消息的附件
     *
     * @var array
     */
    public $attachments = [];

    /**
     * The raw attachments for the message.
	 * 消息的原始附件
     *
     * @var array
     */
    public $rawAttachments = [];

    /**
     * Priority level of the message.
	 * 消息的优先级
     *
     * @var int
     */
    public $priority;

    /**
     * The callbacks for the message.
	 * 消息的回调
     *
     * @var array
     */
    public $callbacks = [];

    /**
     * Set the view for the mail message.
	 * 设置邮件消息的视图
     *
     * @param  array|string  $view
     * @param  array  $data
     * @return $this
     */
    public function view($view, array $data = [])
    {
        $this->view = $view;
        $this->viewData = $data;

        $this->markdown = null;

        return $this;
    }

    /**
     * Set the Markdown template for the notification.
	 * 设置通知的Markdown模板
     *
     * @param  string  $view
     * @param  array  $data
     * @return $this
     */
    public function markdown($view, array $data = [])
    {
        $this->markdown = $view;
        $this->viewData = $data;

        $this->view = null;

        return $this;
    }

    /**
     * Set the default markdown template.
	 * 设置默认降价模板
     *
     * @param  string  $template
     * @return $this
     */
    public function template($template)
    {
        $this->markdown = $template;

        return $this;
    }

    /**
     * Set the theme to use with the Markdown template.
	 * 设置要与Markdown模板一起使用的主题
     *
     * @param  string  $theme
     * @return $this
     */
    public function theme($theme)
    {
        $this->theme = $theme;

        return $this;
    }

    /**
     * Set the from address for the mail message.
	 * 设置邮件消息的发件人地址
     *
     * @param  string  $address
     * @param  string|null  $name
     * @return $this
     */
    public function from($address, $name = null)
    {
        $this->from = [$address, $name];

        return $this;
    }

    /**
     * Set the "reply to" address of the message.
	 * 设置邮件的"回复"地址
     *
     * @param  array|string  $address
     * @param  string|null  $name
     * @return $this
     */
    public function replyTo($address, $name = null)
    {
        if ($this->arrayOfAddresses($address)) {
            $this->replyTo += $this->parseAddresses($address);
        } else {
            $this->replyTo[] = [$address, $name];
        }

        return $this;
    }

    /**
     * Set the cc address for the mail message.
	 * 设置邮件的抄送地址
     *
     * @param  array|string  $address
     * @param  string|null  $name
     * @return $this
     */
    public function cc($address, $name = null)
    {
        if ($this->arrayOfAddresses($address)) {
            $this->cc += $this->parseAddresses($address);
        } else {
            $this->cc[] = [$address, $name];
        }

        return $this;
    }

    /**
     * Set the bcc address for the mail message.
	 * 设置邮件的密送地址
     *
     * @param  array|string  $address
     * @param  string|null  $name
     * @return $this
     */
    public function bcc($address, $name = null)
    {
        if ($this->arrayOfAddresses($address)) {
            $this->bcc += $this->parseAddresses($address);
        } else {
            $this->bcc[] = [$address, $name];
        }

        return $this;
    }

    /**
     * Attach a file to the message.
	 * 附加文件到消息中
     *
     * @param  string  $file
     * @param  array  $options
     * @return $this
     */
    public function attach($file, array $options = [])
    {
        $this->attachments[] = compact('file', 'options');

        return $this;
    }

    /**
     * Attach in-memory data as an attachment.
	 * 附加内存中的数据作为附件
     *
     * @param  string  $data
     * @param  string  $name
     * @param  array  $options
     * @return $this
     */
    public function attachData($data, $name, array $options = [])
    {
        $this->rawAttachments[] = compact('data', 'name', 'options');

        return $this;
    }

    /**
     * Set the priority of this message.
	 * 设置此消息的优先级
     *
     * The value is an integer where 1 is the highest priority and 5 is the lowest.
     *
     * @param  int  $level
     * @return $this
     */
    public function priority($level)
    {
        $this->priority = $level;

        return $this;
    }

    /**
     * Get the data array for the mail message.
	 * 得到邮件消息的数据数组
     *
     * @return array
     */
    public function data()
    {
        return array_merge($this->toArray(), $this->viewData);
    }

    /**
     * Parse the multi-address array into the necessary format.
	 * 将多地址数组解析为必要的格式
     *
     * @param  array  $value
     * @return array
     */
    protected function parseAddresses($value)
    {
        return collect($value)->map(function ($address, $name) {
            return [$address, is_numeric($name) ? null : $name];
        })->values()->all();
    }

    /**
     * Determine if the given "address" is actually an array of addresses.
	 * 确定给定的"address"是否实际上是一个地址数组
     *
     * @param  mixed  $address
     * @return bool
     */
    protected function arrayOfAddresses($address)
    {
        return is_array($address) ||
               $address instanceof Arrayable ||
               $address instanceof Traversable;
    }

    /**
     * Render the mail notification message into an HTML string.
	 * 将邮件通知消息呈现为HTML字符串
     *
     * @return string
     */
    public function render()
    {
        if (isset($this->view)) {
            return Container::getInstance()->make('mailer')->render(
                $this->view, $this->data()
            );
        }

        return Container::getInstance()
            ->make(Markdown::class)
            ->render($this->markdown, $this->data());
    }

    /**
     * Register a callback to be called with the Swift message instance.
	 * 注册一个回调函数在Swift消息实例中
     *
     * @param  callable  $callback
     * @return $this
     */
    public function withSwiftMessage($callback)
    {
        $this->callbacks[] = $callback;

        return $this;
    }
}

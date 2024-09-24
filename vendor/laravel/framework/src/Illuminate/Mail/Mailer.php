<?php
/**
 * 发邮件
 */

namespace Illuminate\Mail;

use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Contracts\Mail\Mailable as MailableContract;
use Illuminate\Contracts\Mail\Mailer as MailerContract;
use Illuminate\Contracts\Mail\MailQueue as MailQueueContract;
use Illuminate\Contracts\Queue\Factory as QueueContract;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Contracts\View\Factory;
use Illuminate\Mail\Events\MessageSending;
use Illuminate\Mail\Events\MessageSent;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Traits\Macroable;
use InvalidArgumentException;
use Swift_Mailer;

class Mailer implements MailerContract, MailQueueContract
{
    use Macroable;

    /**
     * The view factory instance.
	 * 预览工厂实例
     *
     * @var \Illuminate\Contracts\View\Factory
     */
    protected $views;

    /**
     * The Swift Mailer instance.
	 * Swift Mailer实例
     *
     * @var \Swift_Mailer
     */
    protected $swift;

    /**
     * The event dispatcher instance.
	 * 事件调度实例
     *
     * @var \Illuminate\Contracts\Events\Dispatcher|null
     */
    protected $events;

    /**
     * The global from address and name.
	 * 全局从地址和名称
     *
     * @var array
     */
    protected $from;

    /**
     * The global reply-to address and name.
	 * 全局回复地址和名称
     *
     * @var array
     */
    protected $replyTo;

    /**
     * The global to address and name.
	 * 全局地址和名称
     *
     * @var array
     */
    protected $to;

    /**
     * The queue factory implementation.
	 * 队列工厂实现
     *
     * @var \Illuminate\Contracts\Queue\Factory
     */
    protected $queue;

    /**
     * Array of failed recipients.
	 * 失败的收件人数组
     *
     * @var array
     */
    protected $failedRecipients = [];

    /**
     * Create a new Mailer instance.
	 * 创建新的Mailer实例
     *
     * @param  \Illuminate\Contracts\View\Factory  $views
     * @param  \Swift_Mailer  $swift
     * @param  \Illuminate\Contracts\Events\Dispatcher|null  $events
     * @return void
     */
    public function __construct(Factory $views, Swift_Mailer $swift, Dispatcher $events = null)
    {
        $this->views = $views;
        $this->swift = $swift;
        $this->events = $events;
    }

    /**
     * Set the global from address and name.
	 * 设置全局的from地址和名称
     *
     * @param  string  $address
     * @param  string|null  $name
     * @return void
     */
    public function alwaysFrom($address, $name = null)
    {
        $this->from = compact('address', 'name');
    }

    /**
     * Set the global reply-to address and name.
	 * 设置全局回复地址和名称
     *
     * @param  string  $address
     * @param  string|null  $name
     * @return void
     */
    public function alwaysReplyTo($address, $name = null)
    {
        $this->replyTo = compact('address', 'name');
    }

    /**
     * Set the global to address and name.
	 * 设置全局变量为地址和名称
     *
     * @param  string  $address
     * @param  string|null  $name
     * @return void
     */
    public function alwaysTo($address, $name = null)
    {
        $this->to = compact('address', 'name');
    }

    /**
     * Begin the process of mailing a mailable class instance.
	 * 开始邮寄可邮寄类实例的过程
     *
     * @param  mixed  $users
     * @return \Illuminate\Mail\PendingMail
     */
    public function to($users)
    {
        return (new PendingMail($this))->to($users);
    }

    /**
     * Begin the process of mailing a mailable class instance.
	 * 开始邮寄可邮寄类实例的过程
     *
     * @param  mixed  $users
     * @return \Illuminate\Mail\PendingMail
     */
    public function cc($users)
    {
        return (new PendingMail($this))->cc($users);
    }

    /**
     * Begin the process of mailing a mailable class instance.
	 * 开始邮寄可邮寄类实例的过程
     *
     * @param  mixed  $users
     * @return \Illuminate\Mail\PendingMail
     */
    public function bcc($users)
    {
        return (new PendingMail($this))->bcc($users);
    }

    /**
     * Send a new message with only an HTML part.
	 * 发送只包含HTML部分的新消息
     *
     * @param  string  $html
     * @param  mixed  $callback
     * @return void
     */
    public function html($html, $callback)
    {
        return $this->send(['html' => new HtmlString($html)], [], $callback);
    }

    /**
     * Send a new message with only a raw text part.
	 * 发送一个只有原始文本部分的新消息
     *
     * @param  string  $text
     * @param  mixed  $callback
     * @return void
     */
    public function raw($text, $callback)
    {
        return $this->send(['raw' => $text], [], $callback);
    }

    /**
     * Send a new message with only a plain part.
	 * 发送一条只包含普通部分的新消息
     *
     * @param  string  $view
     * @param  array  $data
     * @param  mixed  $callback
     * @return void
     */
    public function plain($view, array $data, $callback)
    {
        return $this->send(['text' => $view], $data, $callback);
    }

    /**
     * Render the given message as a view.
	 * 呈现给定的消息为视图
     *
     * @param  string|array  $view
     * @param  array  $data
     * @return string
     */
    public function render($view, array $data = [])
    {
        // First we need to parse the view, which could either be a string or an array
        // containing both an HTML and plain text versions of the view which should
        // be used when sending an e-mail. We will extract both of them out here.
		// 首先，我们需要解析视图，它可以是字符串或数组，其中包含发送电子邮件时应使用的视图的HTML和纯文本版本。
		// 我们将在这里把它们都提取出来。
        [$view, $plain, $raw] = $this->parseView($view);

        $data['message'] = $this->createMessage();

        return $this->renderView($view ?: $plain, $data);
    }

    /**
     * Send a new message using a view.
	 * 发送新消息使用视图
     *
     * @param  \Illuminate\Contracts\Mail\Mailable|string|array  $view
     * @param  array  $data
     * @param  \Closure|string|null  $callback
     * @return void
     */
    public function send($view, array $data = [], $callback = null)
    {
        if ($view instanceof MailableContract) {
            return $this->sendMailable($view);
        }

        // First we need to parse the view, which could either be a string or an array
        // containing both an HTML and plain text versions of the view which should
        // be used when sending an e-mail. We will extract both of them out here.
		// 首先，我们需要解析视图，它可以是字符串或数组，其中包含发送电子邮件时应使用的视图的HTML和纯文本版本。
		// 我们将在这里把它们都提取出来。
        [$view, $plain, $raw] = $this->parseView($view);

        $data['message'] = $message = $this->createMessage();

        // Once we have retrieved the view content for the e-mail we will set the body
        // of this message using the HTML type, which will provide a simple wrapper
        // to creating view based emails that are able to receive arrays of data.
		// 检索到电子邮件的视图内容后，我们将使用HTML类型设置此消息的正文，
		// 这将为创建能够接收数据数组的基于视图的电子邮件提供一个简单的包装器。
        $callback($message);

        $this->addContent($message, $view, $plain, $raw, $data);

        // If a global "to" address has been set, we will set that address on the mail
        // message. This is primarily useful during local development in which each
        // message should be delivered into a single mail address for inspection.
		// 如果已设置全局“收件人”地址，我们将在邮件中设置该地址。这在本地开发过程中非常有用，
		// 在本地开发中，每条消息都应该被传递到一个邮件地址以供检查。
        if (isset($this->to['address'])) {
            $this->setGlobalToAndRemoveCcAndBcc($message);
        }

        // Next we will determine if the message should be sent. We give the developer
        // one final chance to stop this message and then we will send it to all of
        // its recipients. We will then fire the sent event for the sent message.
		// 接下来，我们将确定是否应该发送消息。我们给开发人员最后一次机会停止此消息，然后将其发送给所有收件人。
		// 然后，我们将为已发送的消息触发已发送事件。
        $swiftMessage = $message->getSwiftMessage();

        if ($this->shouldSendMessage($swiftMessage, $data)) {
            $this->sendSwiftMessage($swiftMessage);

            $this->dispatchSentEvent($message, $data);
        }
    }

    /**
     * Send the given mailable.
	 * 发送给定的邮件
     *
     * @param  \Illuminate\Contracts\Mail\Mailable  $mailable
     * @return mixed
     */
    protected function sendMailable(MailableContract $mailable)
    {
        return $mailable instanceof ShouldQueue
                        ? $mailable->queue($this->queue)
                        : $mailable->send($this);
    }

    /**
     * Parse the given view name or array.
	 * 解析给定的视图名或数组
     *
     * @param  string|array  $view
     * @return array
     *
     * @throws \InvalidArgumentException
     */
    protected function parseView($view)
    {
        if (is_string($view)) {
            return [$view, null, null];
        }

        // If the given view is an array with numeric keys, we will just assume that
        // both a "pretty" and "plain" view were provided, so we will return this
        // array as is, since it should contain both views with numerical keys.
		// 如果给定的视图是一个带有数字键的数组，我们只会假设同时提供了"漂亮"和"普通"视图，
		// 因此我们将按原样返回此数组，因为它应该包含两个带数字键的视图。
        if (is_array($view) && isset($view[0])) {
            return [$view[0], $view[1], null];
        }

        // If this view is an array but doesn't contain numeric keys, we will assume
        // the views are being explicitly specified and will extract them via the
        // named keys instead, allowing the developers to use one or the other.
		// 如果此视图是一个数组，但不包含数字键，我们将假设这些视图是显式指定的，
		// 并将通过命名键提取它们，允许开发人员使用其中一个。
        if (is_array($view)) {
            return [
                $view['html'] ?? null,
                $view['text'] ?? null,
                $view['raw'] ?? null,
            ];
        }

        throw new InvalidArgumentException('Invalid view.');
    }

    /**
     * Add the content to a given message.
	 * 添加内容到给定消息中
     *
     * @param  \Illuminate\Mail\Message  $message
     * @param  string  $view
     * @param  string  $plain
     * @param  string  $raw
     * @param  array  $data
     * @return void
     */
    protected function addContent($message, $view, $plain, $raw, $data)
    {
        if (isset($view)) {
            $message->setBody($this->renderView($view, $data) ?: ' ', 'text/html');
        }

        if (isset($plain)) {
            $method = isset($view) ? 'addPart' : 'setBody';

            $message->$method($this->renderView($plain, $data) ?: ' ', 'text/plain');
        }

        if (isset($raw)) {
            $method = (isset($view) || isset($plain)) ? 'addPart' : 'setBody';

            $message->$method($raw, 'text/plain');
        }
    }

    /**
     * Render the given view.
	 * 呈现给定的视图
     *
     * @param  string  $view
     * @param  array  $data
     * @return string
     */
    protected function renderView($view, $data)
    {
        return $view instanceof Htmlable
                        ? $view->toHtml()
                        : $this->views->make($view, $data)->render();
    }

    /**
     * Set the global "to" address on the given message.
	 * 设置全局"to"地址在给定消息上
     *
     * @param  \Illuminate\Mail\Message  $message
     * @return void
     */
    protected function setGlobalToAndRemoveCcAndBcc($message)
    {
        $message->to($this->to['address'], $this->to['name'], true);
        $message->cc(null, null, true);
        $message->bcc(null, null, true);
    }

    /**
     * Queue a new e-mail message for sending.
	 * 将要发送的新电子邮件排队
     *
     * @param  \Illuminate\Contracts\Mail\Mailable|string|array  $view
     * @param  string|null  $queue
     * @return mixed
     *
     * @throws \InvalidArgumentException
     */
    public function queue($view, $queue = null)
    {
        if (! $view instanceof MailableContract) {
            throw new InvalidArgumentException('Only mailables may be queued.');
        }

        if (is_string($queue)) {
            $view->onQueue($queue);
        }

        return $view->queue($this->queue);
    }

    /**
     * Queue a new e-mail message for sending on the given queue.
	 * 将要在给定队列上发送的新电子邮件放入队列
     *
     * @param  string  $queue
     * @param  \Illuminate\Contracts\Mail\Mailable  $view
     * @return mixed
     */
    public function onQueue($queue, $view)
    {
        return $this->queue($view, $queue);
    }

    /**
     * Queue a new e-mail message for sending on the given queue.
	 * 将要在给定队列上发送的新电子邮件放入队列
     *
     * This method didn't match rest of framework's "onQueue" phrasing. Added "onQueue".
     *
     * @param  string  $queue
     * @param  \Illuminate\Contracts\Mail\Mailable  $view
     * @return mixed
     */
    public function queueOn($queue, $view)
    {
        return $this->onQueue($queue, $view);
    }

    /**
     * Queue a new e-mail message for sending after (n) seconds.
	 * 等待(n)秒后发送新的电子邮件
     *
     * @param  \DateTimeInterface|\DateInterval|int  $delay
     * @param  \Illuminate\Contracts\Mail\Mailable  $view
     * @param  string|null  $queue
     * @return mixed
     *
     * @throws \InvalidArgumentException
     */
    public function later($delay, $view, $queue = null)
    {
        if (! $view instanceof MailableContract) {
            throw new InvalidArgumentException('Only mailables may be queued.');
        }

        return $view->later($delay, is_null($queue) ? $this->queue : $queue);
    }

    /**
     * Queue a new e-mail message for sending after (n) seconds on the given queue.
	 * 在给定队列上等待(n)秒后发送的新电子邮件消息
     *
     * @param  string  $queue
     * @param  \DateTimeInterface|\DateInterval|int  $delay
     * @param  \Illuminate\Contracts\Mail\Mailable  $view
     * @return mixed
     */
    public function laterOn($queue, $delay, $view)
    {
        return $this->later($delay, $view, $queue);
    }

    /**
     * Create a new message instance.
	 * 创建新的消息实例
     *
     * @return \Illuminate\Mail\Message
     */
    protected function createMessage()
    {
        $message = new Message($this->swift->createMessage('message'));

        // If a global from address has been specified we will set it on every message
        // instance so the developer does not have to repeat themselves every time
        // they create a new message. We'll just go ahead and push this address.
		// 如果指定了全局发件人地址，我们将在每个消息实例上设置它，这样开发人员就不必每次创建新消息时都重复自己。
		// 我们只需继续推进这个地址。
        if (! empty($this->from['address'])) {
            $message->from($this->from['address'], $this->from['name']);
        }

        // When a global reply address was specified we will set this on every message
        // instance so the developer does not have to repeat themselves every time
        // they create a new message. We will just go ahead and push this address.
		// 当指定全局回复地址时，我们将在每个消息实例上设置此地址，这样开发人员就不必每次创建新消息时都重复自己。
		// 我们将继续推进这个地址。
        if (! empty($this->replyTo['address'])) {
            $message->replyTo($this->replyTo['address'], $this->replyTo['name']);
        }

        return $message;
    }

    /**
     * Send a Swift Message instance.
	 * 发送一个Swift消息实例
     *
     * @param  \Swift_Message  $message
     * @return int|null
     */
    protected function sendSwiftMessage($message)
    {
        $this->failedRecipients = [];

        try {
            return $this->swift->send($message, $this->failedRecipients);
        } finally {
            $this->forceReconnection();
        }
    }

    /**
     * Determines if the message can be sent.
	 * 确定是否可以发送消息
     *
     * @param  \Swift_Message  $message
     * @param  array  $data
     * @return bool
     */
    protected function shouldSendMessage($message, $data = [])
    {
        if (! $this->events) {
            return true;
        }

        return $this->events->until(
            new MessageSending($message, $data)
        ) !== false;
    }

    /**
     * Dispatch the message sent event.
	 * 分派消息发送事件
     *
     * @param  \Illuminate\Mail\Message  $message
     * @param  array  $data
     * @return void
     */
    protected function dispatchSentEvent($message, $data = [])
    {
        if ($this->events) {
            $this->events->dispatch(
                new MessageSent($message->getSwiftMessage(), $data)
            );
        }
    }

    /**
     * Force the transport to re-connect.
	 * 强制传输重新连接
     *
     * This will prevent errors in daemon queue situations.
     *
     * @return void
     */
    protected function forceReconnection()
    {
        $this->getSwiftMailer()->getTransport()->stop();
    }

    /**
     * Get the array of failed recipients.
	 * 得到失败收件人的数组
     *
     * @return array
     */
    public function failures()
    {
        return $this->failedRecipients;
    }

    /**
     * Get the Swift Mailer instance.
	 * 得到Swift Mailer实例
     *
     * @return \Swift_Mailer
     */
    public function getSwiftMailer()
    {
        return $this->swift;
    }

    /**
     * Get the view factory instance.
	 * 得到视图工厂实例
     *
     * @return \Illuminate\Contracts\View\Factory
     */
    public function getViewFactory()
    {
        return $this->views;
    }

    /**
     * Set the Swift Mailer instance.
	 * 设置Swift Mailer实例
     *
     * @param  \Swift_Mailer  $swift
     * @return void
     */
    public function setSwiftMailer($swift)
    {
        $this->swift = $swift;
    }

    /**
     * Set the queue manager instance.
	 * 设置队列管理实例
     *
     * @param  \Illuminate\Contracts\Queue\Factory  $queue
     * @return $this
     */
    public function setQueue(QueueContract $queue)
    {
        $this->queue = $queue;

        return $this;
    }
}

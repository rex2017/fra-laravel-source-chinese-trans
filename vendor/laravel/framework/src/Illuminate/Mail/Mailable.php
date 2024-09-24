<?php
/**
 * 邮件可邮寄的
 */

namespace Illuminate\Mail;

use Illuminate\Container\Container;
use Illuminate\Contracts\Filesystem\Factory as FilesystemFactory;
use Illuminate\Contracts\Mail\Mailable as MailableContract;
use Illuminate\Contracts\Mail\Mailer as MailerContract;
use Illuminate\Contracts\Queue\Factory as Queue;
use Illuminate\Contracts\Support\Renderable;
use Illuminate\Support\Collection;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Str;
use Illuminate\Support\Traits\ForwardsCalls;
use Illuminate\Support\Traits\Localizable;
use ReflectionClass;
use ReflectionProperty;

class Mailable implements MailableContract, Renderable
{
    use ForwardsCalls, Localizable;

    /**
     * The locale of the message.
	 * 本地信息
     *
     * @var string
     */
    public $locale;

    /**
     * The person the message is from.
	 * 从，发件人
     *
     * @var array
     */
    public $from = [];

    /**
     * The "to" recipients of the message.
	 * 至，收件人
     *
     * @var array
     */
    public $to = [];

    /**
     * The "cc" recipients of the message.
	 * 抄送
     *
     * @var array
     */
    public $cc = [];

    /**
     * The "bcc" recipients of the message.
     *
     * @var array
     */
    public $bcc = [];

    /**
     * The "reply to" recipients of the message.
	 * 回复
     *
     * @var array
     */
    public $replyTo = [];

    /**
     * The subject of the message.
	 * 主题
     *
     * @var string
     */
    public $subject;

    /**
     * The Markdown template for the message (if applicable).
	 * Markdown模板
     *
     * @var string
     */
    protected $markdown;

    /**
     * The HTML to use for the message.
	 * HTML
     *
     * @var string
     */
    protected $html;

    /**
     * The view to use for the message.
	 * 预览
     *
     * @var string
     */
    public $view;

    /**
     * The plain text view to use for the message.
	 * 要用于消息的纯文本视图
     *
     * @var string
     */
    public $textView;

    /**
     * The view data for the message.
	 * 视图数据
     *
     * @var array
     */
    public $viewData = [];

    /**
     * The attachments for the message.
	 * 附件
     *
     * @var array
     */
    public $attachments = [];

    /**
     * The raw attachments for the message.
	 * 原始附件
     *
     * @var array
     */
    public $rawAttachments = [];

    /**
     * The attachments from a storage disk.
	 * 存储磁盘的附件
     *
     * @var array
     */
    public $diskAttachments = [];

    /**
     * The callbacks for the message.
	 * 回调
     *
     * @var array
     */
    public $callbacks = [];

    /**
     * The callback that should be invoked while building the view data.
	 * 在构建视图数据时应该调用的回调
     *
     * @var callable
     */
    public static $viewDataCallback;

    /**
     * Send the message using the given mailer.
	 * 发送消息使用给定的邮件发送器
     *
     * @param  \Illuminate\Contracts\Mail\Mailer  $mailer
     * @return void
     */
    public function send(MailerContract $mailer)
    {
        return $this->withLocale($this->locale, function () use ($mailer) {
            Container::getInstance()->call([$this, 'build']);

            return $mailer->send($this->buildView(), $this->buildViewData(), function ($message) {
                $this->buildFrom($message)
                     ->buildRecipients($message)
                     ->buildSubject($message)
                     ->runCallbacks($message)
                     ->buildAttachments($message);
            });
        });
    }

    /**
     * Queue the message for sending.
	 * 排队消息等待发送
     *
     * @param  \Illuminate\Contracts\Queue\Factory  $queue
     * @return mixed
     */
    public function queue(Queue $queue)
    {
        if (isset($this->delay)) {
            return $this->later($this->delay, $queue);
        }

        $connection = property_exists($this, 'connection') ? $this->connection : null;

        $queueName = property_exists($this, 'queue') ? $this->queue : null;

        return $queue->connection($connection)->pushOn(
            $queueName ?: null, $this->newQueuedJob()
        );
    }

    /**
     * Deliver the queued message after the given delay.
	 * 交付排队消息在给定的延迟之后
     *
     * @param  \DateTimeInterface|\DateInterval|int  $delay
     * @param  \Illuminate\Contracts\Queue\Factory  $queue
     * @return mixed
     */
    public function later($delay, Queue $queue)
    {
        $connection = property_exists($this, 'connection') ? $this->connection : null;

        $queueName = property_exists($this, 'queue') ? $this->queue : null;

        return $queue->connection($connection)->laterOn(
            $queueName ?: null, $delay, $this->newQueuedJob()
        );
    }

    /**
     * Make the queued mailable job instance.
	 * 创建排队可投递作业实例
     *
     * @return mixed
     */
    protected function newQueuedJob()
    {
        return new SendQueuedMailable($this);
    }

    /**
     * Render the mailable into a view.
	 * 呈现邮件到视图
     *
     * @return string
     *
     * @throws \ReflectionException
     */
    public function render()
    {
        return $this->withLocale($this->locale, function () {
            Container::getInstance()->call([$this, 'build']);

            return Container::getInstance()->make('mailer')->render(
                $this->buildView(), $this->buildViewData()
            );
        });
    }

    /**
     * Build the view for the message.
	 * 构建视图
     *
     * @return array|string
     *
     * @throws \ReflectionException
     */
    protected function buildView()
    {
        if (isset($this->html)) {
            return array_filter([
                'html' => new HtmlString($this->html),
                'text' => $this->textView ?? null,
            ]);
        }

        if (isset($this->markdown)) {
            return $this->buildMarkdownView();
        }

        if (isset($this->view, $this->textView)) {
            return [$this->view, $this->textView];
        } elseif (isset($this->textView)) {
            return ['text' => $this->textView];
        }

        return $this->view;
    }

    /**
     * Build the Markdown view for the message.
	 * 构建Markdown视图
     *
     * @return array
     *
     * @throws \ReflectionException
     */
    protected function buildMarkdownView()
    {
        $markdown = Container::getInstance()->make(Markdown::class);

        if (isset($this->theme)) {
            $markdown->theme($this->theme);
        }

        $data = $this->buildViewData();

        return [
            'html' => $markdown->render($this->markdown, $data),
            'text' => $this->buildMarkdownText($markdown, $data),
        ];
    }

    /**
     * Build the view data for the message.
	 * 构建视图数据
     *
     * @return array
     *
     * @throws \ReflectionException
     */
    public function buildViewData()
    {
        $data = $this->viewData;

        if (static::$viewDataCallback) {
            $data = array_merge($data, call_user_func(static::$viewDataCallback, $this));
        }

        foreach ((new ReflectionClass($this))->getProperties(ReflectionProperty::IS_PUBLIC) as $property) {
            if ($property->getDeclaringClass()->getName() !== self::class) {
                $data[$property->getName()] = $property->getValue($this);
            }
        }

        return $data;
    }

    /**
     * Build the text view for a Markdown message.
	 * 构建文本视图为Markdown信息
     *
     * @param  \Illuminate\Mail\Markdown  $markdown
     * @param  array  $data
     * @return string
     */
    protected function buildMarkdownText($markdown, $data)
    {
        return $this->textView
                ?? $markdown->renderText($this->markdown, $data);
    }

    /**
     * Add the sender to the message.
	 * 添加发送者到信息中
     *
     * @param  \Illuminate\Mail\Message  $message
     * @return $this
     */
    protected function buildFrom($message)
    {
        if (! empty($this->from)) {
            $message->from($this->from[0]['address'], $this->from[0]['name']);
        }

        return $this;
    }

    /**
     * Add all of the recipients to the message.
	 * 添加所有收件人到邮件中
     *
     * @param  \Illuminate\Mail\Message  $message
     * @return $this
     */
    protected function buildRecipients($message)
    {
        foreach (['to', 'cc', 'bcc', 'replyTo'] as $type) {
            foreach ($this->{$type} as $recipient) {
                $message->{$type}($recipient['address'], $recipient['name']);
            }
        }

        return $this;
    }

    /**
     * Set the subject for the message.
	 * 设置主题为邮件
     *
     * @param  \Illuminate\Mail\Message  $message
     * @return $this
     */
    protected function buildSubject($message)
    {
        if ($this->subject) {
            $message->subject($this->subject);
        } else {
            $message->subject(Str::title(Str::snake(class_basename($this), ' ')));
        }

        return $this;
    }

    /**
     * Add all of the attachments to the message.
	 * 添加所有附件到消息中
     *
     * @param  \Illuminate\Mail\Message  $message
     * @return $this
     */
    protected function buildAttachments($message)
    {
        foreach ($this->attachments as $attachment) {
            $message->attach($attachment['file'], $attachment['options']);
        }

        foreach ($this->rawAttachments as $attachment) {
            $message->attachData(
                $attachment['data'], $attachment['name'], $attachment['options']
            );
        }

        $this->buildDiskAttachments($message);

        return $this;
    }

    /**
     * Add all of the disk attachments to the message.
	 * 添加所有磁盘附件到消息中
     *
     * @param  \Illuminate\Mail\Message  $message
     * @return void
     */
    protected function buildDiskAttachments($message)
    {
        foreach ($this->diskAttachments as $attachment) {
            $storage = Container::getInstance()->make(
                FilesystemFactory::class
            )->disk($attachment['disk']);

            $message->attachData(
                $storage->get($attachment['path']),
                $attachment['name'] ?? basename($attachment['path']),
                array_merge(['mime' => $storage->mimeType($attachment['path'])], $attachment['options'])
            );
        }
    }

    /**
     * Run the callbacks for the message.
	 * 运行信息的回调
     *
     * @param  \Illuminate\Mail\Message  $message
     * @return $this
     */
    protected function runCallbacks($message)
    {
        foreach ($this->callbacks as $callback) {
            $callback($message->getSwiftMessage());
        }

        return $this;
    }

    /**
     * Set the locale of the message.
	 * 设置消息的区域设置
     *
     * @param  string  $locale
     * @return $this
     */
    public function locale($locale)
    {
        $this->locale = $locale;

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
    public function priority($level = 3)
    {
        $this->callbacks[] = function ($message) use ($level) {
            $message->setPriority($level);
        };

        return $this;
    }

    /**
     * Set the sender of the message.
	 * 设置消息的发送者
     *
     * @param  object|array|string  $address
     * @param  string|null  $name
     * @return $this
     */
    public function from($address, $name = null)
    {
        return $this->setAddress($address, $name, 'from');
    }

    /**
     * Determine if the given recipient is set on the mailable.
	 * 确定是否在邮件上设置了给定的收件人
     *
     * @param  object|array|string  $address
     * @param  string|null  $name
     * @return bool
     */
    public function hasFrom($address, $name = null)
    {
        return $this->hasRecipient($address, $name, 'from');
    }

    /**
     * Set the recipients of the message.
	 * 设置邮件的收件人
     *
     * @param  object|array|string  $address
     * @param  string|null  $name
     * @return $this
     */
    public function to($address, $name = null)
    {
        return $this->setAddress($address, $name, 'to');
    }

    /**
     * Determine if the given recipient is set on the mailable.
	 * 确定是否在邮件上设置了给定的收件人
     *
     * @param  object|array|string  $address
     * @param  string|null  $name
     * @return bool
     */
    public function hasTo($address, $name = null)
    {
        return $this->hasRecipient($address, $name, 'to');
    }

    /**
     * Set the recipients of the message.
	 * 设置邮件的收件人
     *
     * @param  object|array|string  $address
     * @param  string|null  $name
     * @return $this
     */
    public function cc($address, $name = null)
    {
        return $this->setAddress($address, $name, 'cc');
    }

    /**
     * Determine if the given recipient is set on the mailable.
	 * 确定是否在邮件上设置了给定的收件人
     *
     * @param  object|array|string  $address
     * @param  string|null  $name
     * @return bool
     */
    public function hasCc($address, $name = null)
    {
        return $this->hasRecipient($address, $name, 'cc');
    }

    /**
     * Set the recipients of the message.
	 * 设置邮件的收件人
     *
     * @param  object|array|string  $address
     * @param  string|null  $name
     * @return $this
     */
    public function bcc($address, $name = null)
    {
        return $this->setAddress($address, $name, 'bcc');
    }

    /**
     * Determine if the given recipient is set on the mailable.
	 * 确定是否在邮件上设置了给定的收件人
     *
     * @param  object|array|string  $address
     * @param  string|null  $name
     * @return bool
     */
    public function hasBcc($address, $name = null)
    {
        return $this->hasRecipient($address, $name, 'bcc');
    }

    /**
     * Set the "reply to" address of the message.
	 * 设置邮件的"回复"地址
     *
     * @param  object|array|string  $address
     * @param  string|null  $name
     * @return $this
     */
    public function replyTo($address, $name = null)
    {
        return $this->setAddress($address, $name, 'replyTo');
    }

    /**
     * Determine if the given replyTo is set on the mailable.
	 * 确定是否在邮件上设置了给定的replyTo
     *
     * @param  object|array|string  $address
     * @param  string|null  $name
     * @return bool
     */
    public function hasReplyTo($address, $name = null)
    {
        return $this->hasRecipient($address, $name, 'replyTo');
    }

    /**
     * Set the recipients of the message.
	 * 设置邮件的收件人
     *
     * All recipients are stored internally as [['name' => ?, 'address' => ?]]
     *
     * @param  object|array|string  $address
     * @param  string|null  $name
     * @param  string  $property
     * @return $this
     */
    protected function setAddress($address, $name = null, $property = 'to')
    {
        foreach ($this->addressesToArray($address, $name) as $recipient) {
            $recipient = $this->normalizeRecipient($recipient);

            $this->{$property}[] = [
                'name' => $recipient->name ?? null,
                'address' => $recipient->email,
            ];
        }

        return $this;
    }

    /**
     * Convert the given recipient arguments to an array.
	 * 转换给定的接收方参数为数组
     *
     * @param  object|array|string  $address
     * @param  string|null  $name
     * @return array
     */
    protected function addressesToArray($address, $name)
    {
        if (! is_array($address) && ! $address instanceof Collection) {
            $address = is_string($name) ? [['name' => $name, 'email' => $address]] : [$address];
        }

        return $address;
    }

    /**
     * Convert the given recipient into an object.
	 * 转换给定的接收方为对象
     *
     * @param  mixed  $recipient
     * @return object
     */
    protected function normalizeRecipient($recipient)
    {
        if (is_array($recipient)) {
            return (object) $recipient;
        } elseif (is_string($recipient)) {
            return (object) ['email' => $recipient];
        }

        return $recipient;
    }

    /**
     * Determine if the given recipient is set on the mailable.
	 * 确定是否在邮件上设置了给定的收件人
     *
     * @param  object|array|string  $address
     * @param  string|null  $name
     * @param  string  $property
     * @return bool
     */
    protected function hasRecipient($address, $name = null, $property = 'to')
    {
        $expected = $this->normalizeRecipient(
            $this->addressesToArray($address, $name)[0]
        );

        $expected = [
            'name' => $expected->name ?? null,
            'address' => $expected->email,
        ];

        return collect($this->{$property})->contains(function ($actual) use ($expected) {
            if (! isset($expected['name'])) {
                return $actual['address'] == $expected['address'];
            }

            return $actual == $expected;
        });
    }

    /**
     * Set the subject of the message.
	 * 设置消息主题
     *
     * @param  string  $subject
     * @return $this
     */
    public function subject($subject)
    {
        $this->subject = $subject;

        return $this;
    }

    /**
     * Set the Markdown template for the message.
	 * 设置消息的文本模板
     *
     * @param  string  $view
     * @param  array  $data
     * @return $this
     */
    public function markdown($view, array $data = [])
    {
        $this->markdown = $view;
        $this->viewData = array_merge($this->viewData, $data);

        return $this;
    }

    /**
     * Set the view and view data for the message.
	 * 设置消息的视图和视图数据
     *
     * @param  string  $view
     * @param  array  $data
     * @return $this
     */
    public function view($view, array $data = [])
    {
        $this->view = $view;
        $this->viewData = array_merge($this->viewData, $data);

        return $this;
    }

    /**
     * Set the rendered HTML content for the message.
	 * 设置消息的呈现的HTML内容
     *
     * @param  string  $html
     * @return $this
     */
    public function html($html)
    {
        $this->html = $html;

        return $this;
    }

    /**
     * Set the plain text view for the message.
	 * 设置消息的纯文本视图
     *
     * @param  string  $textView
     * @param  array  $data
     * @return $this
     */
    public function text($textView, array $data = [])
    {
        $this->textView = $textView;
        $this->viewData = array_merge($this->viewData, $data);

        return $this;
    }

    /**
     * Set the view data for the message.
	 * 设置消息的视图数据
     *
     * @param  string|array  $key
     * @param  mixed  $value
     * @return $this
     */
    public function with($key, $value = null)
    {
        if (is_array($key)) {
            $this->viewData = array_merge($this->viewData, $key);
        } else {
            $this->viewData[$key] = $value;
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
        $this->attachments = collect($this->attachments)
                    ->push(compact('file', 'options'))
                    ->unique('file')
                    ->all();

        return $this;
    }

    /**
     * Attach a file to the message from storage.
	 * 附加文件到消息中从存储
     *
     * @param  string  $path
     * @param  string|null  $name
     * @param  array  $options
     * @return $this
     */
    public function attachFromStorage($path, $name = null, array $options = [])
    {
        return $this->attachFromStorageDisk(null, $path, $name, $options);
    }

    /**
     * Attach a file to the message from storage.
	 * 附加文件到消息中从存储
     *
     * @param  string  $disk
     * @param  string  $path
     * @param  string|null  $name
     * @param  array  $options
     * @return $this
     */
    public function attachFromStorageDisk($disk, $path, $name = null, array $options = [])
    {
        $this->diskAttachments = collect($this->diskAttachments)->push([
            'disk' => $disk,
            'path' => $path,
            'name' => $name ?? basename($path),
            'options' => $options,
        ])->unique(function ($file) {
            return $file['name'].$file['disk'].$file['path'];
        })->all();

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
        $this->rawAttachments = collect($this->rawAttachments)
                ->push(compact('data', 'name', 'options'))
                ->unique(function ($file) {
                    return $file['name'].$file['data'];
                })->all();

        return $this;
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

    /**
     * Register a callback to be called while building the view data.
	 * 注册一个在构建视图数据时调用的回调
     *
     * @param  callable  $callback
     * @return void
     */
    public static function buildViewDataUsing(callable $callback)
    {
        static::$viewDataCallback = $callback;
    }

    /**
     * Dynamically bind parameters to the message.
	 * 动态绑定参数
     *
     * @param  string  $method
     * @param  array  $parameters
     * @return $this
     *
     * @throws \BadMethodCallException
     */
    public function __call($method, $parameters)
    {
        if (Str::startsWith($method, 'with')) {
            return $this->with(Str::camel(substr($method, 4)), $parameters[0]);
        }

        static::throwBadMethodCallException($method);
    }
}

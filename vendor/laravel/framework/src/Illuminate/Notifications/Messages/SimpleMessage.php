<?php
/**
 * 通知，简单通知
 */

namespace Illuminate\Notifications\Messages;

use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Notifications\Action;

class SimpleMessage
{
    /**
     * The "level" of the notification (info, success, error).
	 * 通知的"级别"(info, success, error)
     *
     * @var string
     */
    public $level = 'info';

    /**
     * The subject of the notification.
	 * 通知的主题
     *
     * @var string
     */
    public $subject;

    /**
     * The notification's greeting.
	 * 通知的问候
     *
     * @var string
     */
    public $greeting;

    /**
     * The notification's salutation.
	 * 通知的称呼
     *
     * @var string
     */
    public $salutation;

    /**
     * The "intro" lines of the notification.
	 * 通知的"介绍"行
     *
     * @var array
     */
    public $introLines = [];

    /**
     * The "outro" lines of the notification.
	 * 通知的"outo"行
     *
     * @var array
     */
    public $outroLines = [];

    /**
     * The text / label for the action.
	 * 操作的文本/标签
     *
     * @var string
     */
    public $actionText;

    /**
     * The action URL.
	 * 动作的URL
     *
     * @var string
     */
    public $actionUrl;

    /**
     * Indicate that the notification gives information about a successful operation.
	 * 指明通知提供有关成功操作的信息
     *
     * @return $this
     */
    public function success()
    {
        $this->level = 'success';

        return $this;
    }

    /**
     * Indicate that the notification gives information about an error.
	 * 指明通知提供有关错误的信息
     *
     * @return $this
     */
    public function error()
    {
        $this->level = 'error';

        return $this;
    }

    /**
     * Set the "level" of the notification (success, error, etc.).
	 * 设置通知的"级别"(成功、错误等)
     *
     * @param  string  $level
     * @return $this
     */
    public function level($level)
    {
        $this->level = $level;

        return $this;
    }

    /**
     * Set the subject of the notification.
	 * 设置通知的主题
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
     * Set the greeting of the notification.
	 * 设置通知的欢迎语
     *
     * @param  string  $greeting
     * @return $this
     */
    public function greeting($greeting)
    {
        $this->greeting = $greeting;

        return $this;
    }

    /**
     * Set the salutation of the notification.
	 * 设置通知的称呼
     *
     * @param  string  $salutation
     * @return $this
     */
    public function salutation($salutation)
    {
        $this->salutation = $salutation;

        return $this;
    }

    /**
     * Add a line of text to the notification.
	 * 添加一行文本向通知
     *
     * @param  mixed  $line
     * @return $this
     */
    public function line($line)
    {
        return $this->with($line);
    }

    /**
     * Add a line of text to the notification.
	 * 添加一行文本向通知
     *
     * @param  mixed  $line
     * @return $this
     */
    public function with($line)
    {
        if ($line instanceof Action) {
            $this->action($line->text, $line->url);
        } elseif (! $this->actionText) {
            $this->introLines[] = $this->formatLine($line);
        } else {
            $this->outroLines[] = $this->formatLine($line);
        }

        return $this;
    }

    /**
     * Format the given line of text.
	 * 格式化给定的文本行
     *
     * @param  \Illuminate\Contracts\Support\Htmlable|string|array  $line
     * @return \Illuminate\Contracts\Support\Htmlable|string
     */
    protected function formatLine($line)
    {
        if ($line instanceof Htmlable) {
            return $line;
        }

        if (is_array($line)) {
            return implode(' ', array_map('trim', $line));
        }

        return trim(implode(' ', array_map('trim', preg_split('/\\r\\n|\\r|\\n/', $line))));
    }

    /**
     * Configure the "call to action" button.
	 * 配置"动作召唤"按钮
     *
     * @param  string  $text
     * @param  string  $url
     * @return $this
     */
    public function action($text, $url)
    {
        $this->actionText = $text;
        $this->actionUrl = $url;

        return $this;
    }

    /**
     * Get an array representation of the message.
	 * 得到消息的数组表示形式
     *
     * @return array
     */
    public function toArray()
    {
        return [
            'level' => $this->level,
            'subject' => $this->subject,
            'greeting' => $this->greeting,
            'salutation' => $this->salutation,
            'introLines' => $this->introLines,
            'outroLines' => $this->outroLines,
            'actionText' => $this->actionText,
            'actionUrl' => $this->actionUrl,
            'displayableActionUrl' => str_replace(['mailto:', 'tel:'], '', $this->actionUrl),
        ];
    }
}

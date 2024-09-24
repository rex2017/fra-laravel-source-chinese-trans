<?php
/**
 * 等待中邮件
 */

namespace Illuminate\Mail;

use Illuminate\Contracts\Mail\Mailable as MailableContract;
use Illuminate\Contracts\Mail\Mailer as MailerContract;
use Illuminate\Contracts\Translation\HasLocalePreference;

class PendingMail
{
    /**
     * The mailer instance.
	 * 邮件实例
     *
     * @var \Illuminate\Contracts\Mail\Mailer
     */
    protected $mailer;

    /**
     * The locale of the message.
	 * 消息的区域设置
     *
     * @var string
     */
    protected $locale;

    /**
     * The "to" recipients of the message.
	 * 消息的"to"收件人
     *
     * @var array
     */
    protected $to = [];

    /**
     * The "cc" recipients of the message.
	 * 消息的"抄送"收件人
     *
     * @var array
     */
    protected $cc = [];

    /**
     * The "bcc" recipients of the message.
	 * 消息的"密件抄送"收件人
     *
     * @var array
     */
    protected $bcc = [];

    /**
     * Create a new mailable mailer instance.
	 * 创建新的可邮件邮件实例
     *
     * @param  \Illuminate\Contracts\Mail\Mailer  $mailer
     * @return void
     */
    public function __construct(MailerContract $mailer)
    {
        $this->mailer = $mailer;
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
     * Set the recipients of the message.
	 * 设置消息的收件人
     *
     * @param  mixed  $users
     * @return $this
     */
    public function to($users)
    {
        $this->to = $users;

        if (! $this->locale && $users instanceof HasLocalePreference) {
            $this->locale($users->preferredLocale());
        }

        return $this;
    }

    /**
     * Set the recipients of the message.
	 * 设置电邮的收件人
     *
     * @param  mixed  $users
     * @return $this
     */
    public function cc($users)
    {
        $this->cc = $users;

        return $this;
    }

    /**
     * Set the recipients of the message.
	 * 设置电邮的收件人
     *
     * @param  mixed  $users
     * @return $this
     */
    public function bcc($users)
    {
        $this->bcc = $users;

        return $this;
    }

    /**
     * Send a new mailable message instance.
	 * 发送一个新的可邮件消息实例
     *
     * @param  \Illuminate\Contracts\Mail\Mailable  $mailable
     * @return mixed
     */
    public function send(MailableContract $mailable)
    {
        return $this->mailer->send($this->fill($mailable));
    }

    /**
     * Send a mailable message immediately.
	 * 立即发送可发送的消息
     *
     * @param  \Illuminate\Contracts\Mail\Mailable  $mailable
     * @return mixed
     *
     * @deprecated Use send() instead.
     */
    public function sendNow(MailableContract $mailable)
    {
        return $this->mailer->send($this->fill($mailable));
    }

    /**
     * Push the given mailable onto the queue.
	 * 将给定的可邮件推送到队列中
     *
     * @param  \Illuminate\Contracts\Mail\Mailable  $mailable
     * @return mixed
     */
    public function queue(MailableContract $mailable)
    {
        return $this->mailer->queue($this->fill($mailable));
    }

    /**
     * Deliver the queued message after the given delay.
	 * 在给定的延迟之后交付排队消息
     *
     * @param  \DateTimeInterface|\DateInterval|int  $delay
     * @param  \Illuminate\Contracts\Mail\Mailable  $mailable
     * @return mixed
     */
    public function later($delay, MailableContract $mailable)
    {
        return $this->mailer->later($delay, $this->fill($mailable));
    }

    /**
     * Populate the mailable with the addresses.
	 * 用地址填充邮件
     *
     * @param  \Illuminate\Contracts\Mail\Mailable  $mailable
     * @return \Illuminate\Mail\Mailable
     */
    protected function fill(MailableContract $mailable)
    {
        return tap($mailable->to($this->to)
            ->cc($this->cc)
            ->bcc($this->bcc), function ($mailable) {
                if ($this->locale) {
                    $mailable->locale($this->locale);
                }
            });
    }
}

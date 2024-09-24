<?php
/**
 * 支持，待处理邮件伪造
 */

namespace Illuminate\Support\Testing\Fakes;

use Illuminate\Contracts\Mail\Mailable;
use Illuminate\Mail\PendingMail;

class PendingMailFake extends PendingMail
{
    /**
     * Create a new instance.
	 * 创建新的实例
     *
     * @param  \Illuminate\Support\Testing\Fakes\MailFake  $mailer
     * @return void
     */
    public function __construct($mailer)
    {
        $this->mailer = $mailer;
    }

    /**
     * Send a new mailable message instance.
	 * 发送新的可邮件消息实例
     *
     * @param  \Illuminate\Contracts\Mail\Mailable  $mailable
     * @return mixed
     */
    public function send(Mailable $mailable)
    {
        return $this->sendNow($mailable);
    }

    /**
     * Send a mailable message immediately.
	 * 立即发送可发送的消息
     *
     * @param  \Illuminate\Contracts\Mail\Mailable  $mailable
     * @return mixed
     */
    public function sendNow(Mailable $mailable)
    {
        return $this->mailer->send($this->fill($mailable));
    }

    /**
     * Push the given mailable onto the queue.
	 * 推送给定的可邮件到队列中
     *
     * @param  \Illuminate\Contracts\Mail\Mailable  $mailable
     * @return mixed
     */
    public function queue(Mailable $mailable)
    {
        return $this->mailer->queue($this->fill($mailable));
    }
}

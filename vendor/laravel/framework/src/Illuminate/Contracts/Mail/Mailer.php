<?php
/**
 * 契约，邮件接口
 */

namespace Illuminate\Contracts\Mail;

interface Mailer
{
    /**
     * Begin the process of mailing a mailable class instance.
	 * to,开启一个进程去发邮件
     *
     * @param  mixed  $users
     * @return \Illuminate\Mail\PendingMail
     */
    public function to($users);

    /**
     * Begin the process of mailing a mailable class instance.
	 * bcc,开启一个进程去发邮件
     *
     * @param  mixed  $users
     * @return \Illuminate\Mail\PendingMail
     */
    public function bcc($users);

    /**
     * Send a new message with only a raw text part.
	 * 草稿
     *
     * @param  string  $text
     * @param  mixed  $callback
     * @return void
     */
    public function raw($text, $callback);

    /**
     * Send a new message using a view.
	 * 发送
     *
     * @param  \Illuminate\Contracts\Mail\Mailable|string|array  $view
     * @param  array  $data
     * @param  \Closure|string|null  $callback
     * @return void
     */
    public function send($view, array $data = [], $callback = null);

    /**
     * Get the array of failed recipients.
	 * 得到失败收件人
     *
     * @return array
     */
    public function failures();
}

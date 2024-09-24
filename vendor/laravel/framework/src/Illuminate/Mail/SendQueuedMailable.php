<?php
/**
 * 发送邮件队列
 */

namespace Illuminate\Mail;

use Illuminate\Contracts\Mail\Mailable as MailableContract;
use Illuminate\Contracts\Mail\Mailer as MailerContract;

class SendQueuedMailable
{
    /**
     * The mailable message instance.
	 * 可用邮件实例
     *
     * @var \Illuminate\Contracts\Mail\Mailable
     */
    public $mailable;

    /**
     * The number of times the job may be attempted.
	 * 可能尝试该作业的次数
     *
     * @var int
     */
    public $tries;

    /**
     * The number of seconds the job can run before timing out.
	 * 作业在超时之前可以运行的秒数
     *
     * @var int
     */
    public $timeout;

    /**
     * Create a new job instance.
	 * 创建新的作业实例
     *
     * @param  \Illuminate\Contracts\Mail\Mailable  $mailable
     * @return void
     */
    public function __construct(MailableContract $mailable)
    {
        $this->mailable = $mailable;
        $this->tries = property_exists($mailable, 'tries') ? $mailable->tries : null;
        $this->timeout = property_exists($mailable, 'timeout') ? $mailable->timeout : null;
    }

    /**
     * Handle the queued job.
	 * 处理排队作业
     *
     * @param  \Illuminate\Contracts\Mail\Mailer  $mailer
     * @return void
     */
    public function handle(MailerContract $mailer)
    {
        $this->mailable->send($mailer);
    }

    /**
     * Get the display name for the queued job.
	 * 得到排队作业的显示名称
     *
     * @return string
     */
    public function displayName()
    {
        return get_class($this->mailable);
    }

    /**
     * Call the failed method on the mailable instance.
	 * 调用失败的方法在可邮件实例上
     *
     * @param  \Exception  $e
     * @return void
     */
    public function failed($e)
    {
        if (method_exists($this->mailable, 'failed')) {
            $this->mailable->failed($e);
        }
    }

    /**
     * Get the retry delay for the mailable object.
	 * 得到可邮寄对象的重试延迟
     *
     * @return mixed
     */
    public function retryAfter()
    {
        if (! method_exists($this->mailable, 'retryAfter') && ! isset($this->mailable->retryAfter)) {
            return;
        }

        return $this->mailable->retryAfter ?? $this->mailable->retryAfter();
    }

    /**
     * Prepare the instance for cloning.
	 * 为克隆准备实例
     *
     * @return void
     */
    public function __clone()
    {
        $this->mailable = clone $this->mailable;
    }
}

<?php
/**
 * 支持，邮件伪造
 */

namespace Illuminate\Support\Testing\Fakes;

use Illuminate\Contracts\Mail\Mailable;
use Illuminate\Contracts\Mail\Mailer;
use Illuminate\Contracts\Mail\MailQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use PHPUnit\Framework\Assert as PHPUnit;

class MailFake implements Mailer, MailQueue
{
    /**
     * All of the mailables that have been sent.
	 * 所有已发送的邮件
     *
     * @var array
     */
    protected $mailables = [];

    /**
     * All of the mailables that have been queued.
	 * 已排队的所有可邮件
     *
     * @var array
     */
    protected $queuedMailables = [];

    /**
     * Assert if a mailable was sent based on a truth-test callback.
	 * 判断邮件是否基于真值测试回调发送
     *
     * @param  string  $mailable
     * @param  callable|int|null  $callback
     * @return void
     */
    public function assertSent($mailable, $callback = null)
    {
        if (is_numeric($callback)) {
            return $this->assertSentTimes($mailable, $callback);
        }

        $message = "The expected [{$mailable}] mailable was not sent.";

        if (count($this->queuedMailables) > 0) {
            $message .= ' Did you mean to use assertQueued() instead?';
        }

        PHPUnit::assertTrue(
            $this->sent($mailable, $callback)->count() > 0,
            $message
        );
    }

    /**
     * Assert if a mailable was sent a number of times.
	 * 判断邮件是否发送了多次
     *
     * @param  string  $mailable
     * @param  int  $times
     * @return void
     */
    protected function assertSentTimes($mailable, $times = 1)
    {
        PHPUnit::assertTrue(
            ($count = $this->sent($mailable)->count()) === $times,
            "The expected [{$mailable}] mailable was sent {$count} times instead of {$times} times."
        );
    }

    /**
     * Determine if a mailable was not sent based on a truth-test callback.
	 * 确定是否未发送可邮件根据真值测试回调
     *
     * @param  string  $mailable
     * @param  callable|null  $callback
     * @return void
     */
    public function assertNotSent($mailable, $callback = null)
    {
        PHPUnit::assertTrue(
            $this->sent($mailable, $callback)->count() === 0,
            "The unexpected [{$mailable}] mailable was sent."
        );
    }

    /**
     * Assert that no mailables were sent.
	 * 断言没有发送邮件
     *
     * @return void
     */
    public function assertNothingSent()
    {
        $mailableNames = collect($this->mailables)->map(function ($mailable) {
            return get_class($mailable);
        })->join(', ');

        PHPUnit::assertEmpty($this->mailables, 'The following mailables were sent unexpectedly: '.$mailableNames);
    }

    /**
     * Assert if a mailable was queued based on a truth-test callback.
	 * 判断是否根据真值测试回调对可邮件进行了排队
     *
     * @param  string  $mailable
     * @param  callable|int|null  $callback
     * @return void
     */
    public function assertQueued($mailable, $callback = null)
    {
        if (is_numeric($callback)) {
            return $this->assertQueuedTimes($mailable, $callback);
        }

        PHPUnit::assertTrue(
            $this->queued($mailable, $callback)->count() > 0,
            "The expected [{$mailable}] mailable was not queued."
        );
    }

    /**
     * Assert if a mailable was queued a number of times.
	 * 判断可邮件是否排队多次
     *
     * @param  string  $mailable
     * @param  int  $times
     * @return void
     */
    protected function assertQueuedTimes($mailable, $times = 1)
    {
        PHPUnit::assertTrue(
            ($count = $this->queued($mailable)->count()) === $times,
            "The expected [{$mailable}] mailable was queued {$count} times instead of {$times} times."
        );
    }

    /**
     * Determine if a mailable was not queued based on a truth-test callback.
	 * 确定可邮件是否未排队根据真值测试回调
     *
     * @param  string  $mailable
     * @param  callable|null  $callback
     * @return void
     */
    public function assertNotQueued($mailable, $callback = null)
    {
        PHPUnit::assertTrue(
            $this->queued($mailable, $callback)->count() === 0,
            "The unexpected [{$mailable}] mailable was queued."
        );
    }

    /**
     * Assert that no mailables were queued.
	 * 断言没有可发送邮件排队
     *
     * @return void
     */
    public function assertNothingQueued()
    {
        $mailableNames = collect($this->queuedMailables)->map(function ($mailable) {
            return get_class($mailable);
        })->join(', ');

        PHPUnit::assertEmpty($this->queuedMailables, 'The following mailables were queued unexpectedly: '.$mailableNames);
    }

    /**
     * Get all of the mailables matching a truth-test callback.
	 * 得到与真值测试回调匹配的所有邮件
     *
     * @param  string  $mailable
     * @param  callable|null  $callback
     * @return \Illuminate\Support\Collection
     */
    public function sent($mailable, $callback = null)
    {
        if (! $this->hasSent($mailable)) {
            return collect();
        }

        $callback = $callback ?: function () {
            return true;
        };

        return $this->mailablesOf($mailable)->filter(function ($mailable) use ($callback) {
            return $callback($mailable);
        });
    }

    /**
     * Determine if the given mailable has been sent.
	 * 确定给定的邮件是否已发送
     *
     * @param  string  $mailable
     * @return bool
     */
    public function hasSent($mailable)
    {
        return $this->mailablesOf($mailable)->count() > 0;
    }

    /**
     * Get all of the queued mailables matching a truth-test callback.
	 * 得到与真值测试回调匹配的所有排队邮件
     *
     * @param  string  $mailable
     * @param  callable|null  $callback
     * @return \Illuminate\Support\Collection
     */
    public function queued($mailable, $callback = null)
    {
        if (! $this->hasQueued($mailable)) {
            return collect();
        }

        $callback = $callback ?: function () {
            return true;
        };

        return $this->queuedMailablesOf($mailable)->filter(function ($mailable) use ($callback) {
            return $callback($mailable);
        });
    }

    /**
     * Determine if the given mailable has been queued.
	 * 确定给定的可邮件是否已排队
     *
     * @param  string  $mailable
     * @return bool
     */
    public function hasQueued($mailable)
    {
        return $this->queuedMailablesOf($mailable)->count() > 0;
    }

    /**
     * Get all of the mailed mailables for a given type.
	 * 得到给定类型的所有已发送邮件
     *
     * @param  string  $type
     * @return \Illuminate\Support\Collection
     */
    protected function mailablesOf($type)
    {
        return collect($this->mailables)->filter(function ($mailable) use ($type) {
            return $mailable instanceof $type;
        });
    }

    /**
     * Get all of the mailed mailables for a given type.
	 * 得到给定类型的所有已发送邮件
     *
     * @param  string  $type
     * @return \Illuminate\Support\Collection
     */
    protected function queuedMailablesOf($type)
    {
        return collect($this->queuedMailables)->filter(function ($mailable) use ($type) {
            return $mailable instanceof $type;
        });
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
        return (new PendingMailFake($this))->to($users);
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
        return (new PendingMailFake($this))->bcc($users);
    }

    /**
     * Send a new message with only a raw text part.
	 * 发送一个只有原始文本部分的新消息
     *
     * @param  string  $text
     * @param  \Closure|string  $callback
     * @return void
     */
    public function raw($text, $callback)
    {
        //
    }

    /**
     * Send a new message using a view.
	 * 使用视图发送新消息
     *
     * @param  string|array  $view
     * @param  array  $data
     * @param  \Closure|string  $callback
     * @return void
     */
    public function send($view, array $data = [], $callback = null)
    {
        if (! $view instanceof Mailable) {
            return;
        }

        if ($view instanceof ShouldQueue) {
            return $this->queue($view, $data);
        }

        $this->mailables[] = $view;
    }

    /**
     * Queue a new e-mail message for sending.
	 * 将要发送的新电子邮件排队
     *
     * @param  \Illuminate\Contracts\Mail\Mailable|string|array  $view
     * @param  string|null  $queue
     * @return mixed
     */
    public function queue($view, $queue = null)
    {
        if (! $view instanceof Mailable) {
            return;
        }

        $this->queuedMailables[] = $view;
    }

    /**
     * Queue a new e-mail message for sending after (n) seconds.
	 * 等待(n)秒后发送新的电子邮件
     *
     * @param  \DateTimeInterface|\DateInterval|int  $delay
     * @param  \Illuminate\Contracts\Mail\Mailable|string|array  $view
     * @param  string  $queue
     * @return mixed
     */
    public function later($delay, $view, $queue = null)
    {
        $this->queue($view, $queue);
    }

    /**
     * Get the array of failed recipients.
	 * 得到失败收件人的数组
     *
     * @return array
     */
    public function failures()
    {
        return [];
    }
}

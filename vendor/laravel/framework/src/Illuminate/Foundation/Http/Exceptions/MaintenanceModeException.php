<?php
/**
 * 基础，维护模式异常
 */

namespace Illuminate\Foundation\Http\Exceptions;

use Exception;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Date;
use Symfony\Component\HttpKernel\Exception\ServiceUnavailableHttpException;

class MaintenanceModeException extends ServiceUnavailableHttpException
{
    /**
     * When the application was put in maintenance mode.
	 * 当应用程序处于维护模式
     *
     * @var \Illuminate\Support\Carbon
     */
    public $wentDownAt;

    /**
     * The number of seconds to wait before retrying.
	 * 重试前等待的秒数
     *
     * @var int
     */
    public $retryAfter;

    /**
     * When the application should next be available.
	 * 应用程序下次可用的时间
     *
     * @var \Illuminate\Support\Carbon
     */
    public $willBeAvailableAt;

    /**
     * Create a new exception instance.
	 * 创建新的异常实例
     *
     * @param  int  $time
     * @param  int|null  $retryAfter
     * @param  string|null  $message
     * @param  \Exception|null  $previous
     * @param  int  $code
     * @return void
     */
    public function __construct($time, $retryAfter = null, $message = null, Exception $previous = null, $code = 0)
    {
        parent::__construct($retryAfter, $message, $previous, $code);

        $this->wentDownAt = Date::createFromTimestamp($time);

        if ($retryAfter) {
            $this->retryAfter = $retryAfter;

            $this->willBeAvailableAt = Date::instance(Carbon::createFromTimestamp($time)->addRealSeconds($this->retryAfter));
        }
    }
}

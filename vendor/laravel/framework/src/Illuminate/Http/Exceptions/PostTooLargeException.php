<?php
/**
 * Http，提交过大异常
 */

namespace Illuminate\Http\Exceptions;

use Exception;
use Symfony\Component\HttpKernel\Exception\HttpException;

class PostTooLargeException extends HttpException
{
    /**
     * Create a new "post too large" exception instance.
	 * 创建新的"post too large"异常实例
     *
     * @param  string|null  $message
     * @param  \Exception|null  $previous
     * @param  array  $headers
     * @param  int  $code
     * @return void
     */
    public function __construct($message = null, Exception $previous = null, array $headers = [], $code = 0)
    {
        parent::__construct(413, $message, $previous, $headers, $code);
    }
}

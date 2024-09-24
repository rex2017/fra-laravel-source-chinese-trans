<?php
/**
 * Http，异常响应
 */

namespace Illuminate\Http\Exceptions;

use RuntimeException;
use Symfony\Component\HttpFoundation\Response;

class HttpResponseException extends RuntimeException
{
    /**
     * The underlying response instance.
	 * 底层响应实例
     *
     * @var \Symfony\Component\HttpFoundation\Response
     */
    protected $response;

    /**
     * Create a new HTTP response exception instance.
	 * 创建新的http异常响应实例
     *
     * @param  \Symfony\Component\HttpFoundation\Response  $response
     * @return void
     */
    public function __construct(Response $response)
    {
        $this->response = $response;
    }

    /**
     * Get the underlying response instance.
	 * 得到底层响应实例
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function getResponse()
    {
        return $this->response;
    }
}

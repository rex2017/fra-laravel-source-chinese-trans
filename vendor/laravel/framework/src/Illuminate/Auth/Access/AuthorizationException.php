<?php
/**
 * 身份，授权异常
 */

namespace Illuminate\Auth\Access;

use Exception;

class AuthorizationException extends Exception
{
    /**
     * The response from the gate.
	 * 大门的响应
     *
     * @var \Illuminate\Auth\Access\Response
     */
    protected $response;

    /**
     * Create a new authorization exception instance.
	 * 创建新的授权异常实例
     *
     * @param  string|null  $message
     * @param  mixed  $code
     * @param  \Exception|null  $previous
     * @return void
     */
    public function __construct($message = null, $code = null, Exception $previous = null)
    {
        parent::__construct($message ?? 'This action is unauthorized.', 0, $previous);

        $this->code = $code ?: 0;
    }

    /**
     * Get the response from the gate.
	 * 得到大门的响应
     *
     * @return \Illuminate\Auth\Access\Response
     */
    public function response()
    {
        return $this->response;
    }

    /**
     * Set the response from the gate.
	 * 设置大门的响应
     *
     * @param  \Illuminate\Auth\Access\Response  $response
     * @return $this
     */
    public function setResponse($response)
    {
        $this->response = $response;

        return $this;
    }

    /**
     * Create a deny response object from this exception.
	 * 创建一个拒绝响应对象从此异常
     *
     * @return \Illuminate\Auth\Access\Response
     */
    public function toResponse()
    {
        return Response::deny($this->message, $this->code);
    }
}

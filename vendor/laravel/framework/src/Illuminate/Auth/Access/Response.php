<?php
/**
 * 身份，响应
 */

namespace Illuminate\Auth\Access;

use Illuminate\Contracts\Support\Arrayable;

class Response implements Arrayable
{
    /**
     * Indicates whether the response was allowed.
	 * 指明是否允许响应
     *
     * @var bool
     */
    protected $allowed;

    /**
     * The response message.
	 * 响应信息
     *
     * @var string|null
     */
    protected $message;

    /**
     * The response code.
	 * 响应代码
     *
     * @var mixed
     */
    protected $code;

    /**
     * Create a new response.
	 * 创建新的响应
     *
     * @param  bool  $allowed
     * @param  string  $message
     * @param  mixed  $code
     * @return void
     */
    public function __construct($allowed, $message = '', $code = null)
    {
        $this->code = $code;
        $this->allowed = $allowed;
        $this->message = $message;
    }

    /**
     * Create a new "allow" Response.
	 * 创建一个新的"allow"响应
     *
     * @param  string|null  $message
     * @param  mixed  $code
     * @return \Illuminate\Auth\Access\Response
     */
    public static function allow($message = null, $code = null)
    {
        return new static(true, $message, $code);
    }

    /**
     * Create a new "deny" Response.
	 * 创建一个新的"deny"响应
     *
     * @param  string|null  $message
     * @param  mixed  $code
     * @return \Illuminate\Auth\Access\Response
     */
    public static function deny($message = null, $code = null)
    {
        return new static(false, $message, $code);
    }

    /**
     * Determine if the response was allowed.
	 * 确定是否响应是允许的
     *
     * @return bool
     */
    public function allowed()
    {
        return $this->allowed;
    }

    /**
     * Determine if the response was denied.
	 * 确定是否响应是禁止的
     *
     * @return bool
     */
    public function denied()
    {
        return ! $this->allowed();
    }

    /**
     * Get the response message.
	 * 得到响应消息
     *
     * @return string|null
     */
    public function message()
    {
        return $this->message;
    }

    /**
     * Get the response code / reason.
	 * 得到响应代码/原因
     *
     * @return mixed
     */
    public function code()
    {
        return $this->code;
    }

    /**
     * Throw authorization exception if response was denied.
	 * 抛出授权异常，如果拒绝响应。
     *
     * @return \Illuminate\Auth\Access\Response
     *
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function authorize()
    {
        if ($this->denied()) {
            throw (new AuthorizationException($this->message(), $this->code()))
                        ->setResponse($this);
        }

        return $this;
    }

    /**
     * Convert the response to an array.
	 * 转换响应为数组
     *
     * @return array
     */
    public function toArray()
    {
        return [
            'allowed' => $this->allowed(),
            'message' => $this->message(),
            'code' => $this->code(),
        ];
    }

    /**
     * Get the string representation of the message.
	 * 得到消息的字符串表示形式
     *
     * @return string
     */
    public function __toString()
    {
        return (string) $this->message();
    }
}

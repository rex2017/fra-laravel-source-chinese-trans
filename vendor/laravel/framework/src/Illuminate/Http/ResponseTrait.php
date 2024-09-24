<?php
/**
 * Http，响应特征
 */

namespace Illuminate\Http;

use Exception;
use Illuminate\Http\Exceptions\HttpResponseException;
use Symfony\Component\HttpFoundation\HeaderBag;

trait ResponseTrait
{
    /**
     * The original content of the response.
	 * 响应原始内容
     *
     * @var mixed
     */
    public $original;

    /**
     * The exception that triggered the error response (if applicable).
	 * 触发错误响应的异常(如果适用)
     *
     * @var \Exception|null
     */
    public $exception;

    /**
     * Get the status code for the response.
	 * 得到响应状态代码
     *
     * @return int
     */
    public function status()
    {
        return $this->getStatusCode();
    }

    /**
     * Get the content of the response.
	 * 得到响应内容
     *
     * @return string
     */
    public function content()
    {
        return $this->getContent();
    }

    /**
     * Get the original response content.
	 * 得到原始响应内容
     *
     * @return mixed
     */
    public function getOriginalContent()
    {
        $original = $this->original;

        return $original instanceof self ? $original->{__FUNCTION__}() : $original;
    }

    /**
     * Set a header on the Response.
	 * 设置响应头
     *
     * @param  string  $key
     * @param  array|string  $values
     * @param  bool  $replace
     * @return $this
     */
    public function header($key, $values, $replace = true)
    {
        $this->headers->set($key, $values, $replace);

        return $this;
    }

    /**
     * Add an array of headers to the response.
	 * 添加一个标题数组至响应
     *
     * @param  \Symfony\Component\HttpFoundation\HeaderBag|array  $headers
     * @return $this
     */
    public function withHeaders($headers)
    {
        if ($headers instanceof HeaderBag) {
            $headers = $headers->all();
        }

        foreach ($headers as $key => $value) {
            $this->headers->set($key, $value);
        }

        return $this;
    }

    /**
     * Add a cookie to the response.
	 * 添加cookie至响应
     *
     * @param  \Symfony\Component\HttpFoundation\Cookie|mixed  $cookie
     * @return $this
     */
    public function cookie($cookie)
    {
        return $this->withCookie(...func_get_args());
    }

    /**
     * Add a cookie to the response.
	 * 添加cookie至响应
     *
     * @param  \Symfony\Component\HttpFoundation\Cookie|mixed  $cookie
     * @return $this
     */
    public function withCookie($cookie)
    {
        if (is_string($cookie) && function_exists('cookie')) {
            $cookie = cookie(...func_get_args());
        }

        $this->headers->setCookie($cookie);

        return $this;
    }

    /**
     * Get the callback of the response.
	 * 得到响应回调
     *
     * @return string|null
     */
    public function getCallback()
    {
        return $this->callback ?? null;
    }

    /**
     * Set the exception to attach to the response.
	 * 设置响应异常
     *
     * @param  \Exception  $e
     * @return $this
     */
    public function withException(Exception $e)
    {
        $this->exception = $e;

        return $this;
    }

    /**
     * Throws the response in a HttpResponseException instance.
	 * 抛出响应
     *
     * @return void
     *
     * @throws \Illuminate\Http\Exceptions\HttpResponseException
     */
    public function throwResponse()
    {
        throw new HttpResponseException($this);
    }
}

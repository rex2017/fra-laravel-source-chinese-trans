<?php
/**
 * 验证异常
 */

namespace Illuminate\Validation;

use Exception;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Validator as ValidatorFacade;

class ValidationException extends Exception
{
    /**
     * The validator instance.
	 * 验证实例
     *
     * @var \Illuminate\Contracts\Validation\Validator
     */
    public $validator;

    /**
     * The recommended response to send to the client.
	 * 建议发送给客户端的响应
     *
     * @var \Symfony\Component\HttpFoundation\Response|null
     */
    public $response;

    /**
     * The status code to use for the response.
	 * 用于响应的状态码
     *
     * @var int
     */
    public $status = 422;

    /**
     * The name of the error bag.
	 * 错误包的名称
     *
     * @var string
     */
    public $errorBag;

    /**
     * The path the client should be redirected to.
	 * 客户端应该重定向到的路径
     *
     * @var string
     */
    public $redirectTo;

    /**
     * Create a new exception instance.
	 * 创建新的异常实例
     *
     * @param  \Illuminate\Contracts\Validation\Validator  $validator
     * @param  \Symfony\Component\HttpFoundation\Response|null  $response
     * @param  string  $errorBag
     * @return void
     */
    public function __construct($validator, $response = null, $errorBag = 'default')
    {
        parent::__construct('The given data was invalid.');

        $this->response = $response;
        $this->errorBag = $errorBag;
        $this->validator = $validator;
    }

    /**
     * Create a new validation exception from a plain array of messages.
	 * 从普通消息数组创建新的验证异常
     *
     * @param  array  $messages
     * @return static
     */
    public static function withMessages(array $messages)
    {
        return new static(tap(ValidatorFacade::make([], []), function ($validator) use ($messages) {
            foreach ($messages as $key => $value) {
                foreach (Arr::wrap($value) as $message) {
                    $validator->errors()->add($key, $message);
                }
            }
        }));
    }

    /**
     * Get all of the validation error messages.
	 * 得到所有验证错误消息
     *
     * @return array
     */
    public function errors()
    {
        return $this->validator->errors()->messages();
    }

    /**
     * Set the HTTP status code to be used for the response.
	 * 设置用于响应的HTTP状态码
     *
     * @param  int  $status
     * @return $this
     */
    public function status($status)
    {
        $this->status = $status;

        return $this;
    }

    /**
     * Set the error bag on the exception.
	 * 设置错误包在异常上
     *
     * @param  string  $errorBag
     * @return $this
     */
    public function errorBag($errorBag)
    {
        $this->errorBag = $errorBag;

        return $this;
    }

    /**
     * Set the URL to redirect to on a validation error.
	 * 设置验证错误时要重定向到的URL
     *
     * @param  string  $url
     * @return $this
     */
    public function redirectTo($url)
    {
        $this->redirectTo = $url;

        return $this;
    }

    /**
     * Get the underlying response instance.
	 * 得到底层响应实例
     *
     * @return \Symfony\Component\HttpFoundation\Response|null
     */
    public function getResponse()
    {
        return $this->response;
    }
}

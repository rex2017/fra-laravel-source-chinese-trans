<?php
/**
 * 闭包验证规则
 */

namespace Illuminate\Validation;

use Illuminate\Contracts\Validation\Rule as RuleContract;

class ClosureValidationRule implements RuleContract
{
    /**
     * The callback that validates the attribute.
	 * 回调验证属性
     *
     * @var \Closure
     */
    public $callback;

    /**
     * Indicates if the validation callback failed.
	 * 确认是否验证回调失败
     *
     * @var bool
     */
    public $failed = false;

    /**
     * The validation error message.
	 * 验证错误信息
     *
     * @var string|null
     */
    public $message;

    /**
     * Create a new Closure based validation rule.
	 * 创建新的闭包基于验证规则
     *
     * @param  \Closure  $callback
     * @return void
     */
    public function __construct($callback)
    {
        $this->callback = $callback;
    }

    /**
     * Determine if the validation rule passes.
	 * 确定是否验证规则通过
     *
     * @param  string  $attribute
     * @param  mixed  $value
     * @return bool
     */
    public function passes($attribute, $value)
    {
        $this->failed = false;

        $this->callback->__invoke($attribute, $value, function ($message) {
            $this->failed = true;

            $this->message = $message;
        });

        return ! $this->failed;
    }

    /**
     * Get the validation error message.
	 * 得到验证错误信息
     *
     * @return string
     */
    public function message()
    {
        return $this->message;
    }
}

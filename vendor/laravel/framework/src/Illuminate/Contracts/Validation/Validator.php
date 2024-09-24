<?php
/**
 * 契约，验证接口
 */

namespace Illuminate\Contracts\Validation;

use Illuminate\Contracts\Support\MessageProvider;

interface Validator extends MessageProvider
{
    /**
     * Run the validator's rules against its data.
	 * 运行验证器规则
     *
     * @return array
     */
    public function validate();

    /**
     * Get the attributes and values that were validated.
	 * 得到被验证属性和值
     *
     * @return array
     */
    public function validated();

    /**
     * Determine if the data fails the validation rules.
	 * 确定是否验证规则失败
     *
     * @return bool
     */
    public function fails();

    /**
     * Get the failed validation rules.
	 * 得到失败验证规则
     *
     * @return array
     */
    public function failed();

    /**
     * Add conditions to a given field based on a Closure.
	 * 根据Closure向给定字段添加条件
     *
     * @param  string|array  $attribute
     * @param  string|array  $rules
     * @param  callable  $callback
     * @return $this
     */
    public function sometimes($attribute, $rules, callable $callback);

    /**
     * Add an after validation callback.
	 * 添加一个验证回调
     *
     * @param  callable|string  $callback
     * @return $this
     */
    public function after($callback);

    /**
     * Get all of the validation error messages.
	 * 得到所有验证错误消息
     *
     * @return \Illuminate\Support\MessageBag
     */
    public function errors();
}

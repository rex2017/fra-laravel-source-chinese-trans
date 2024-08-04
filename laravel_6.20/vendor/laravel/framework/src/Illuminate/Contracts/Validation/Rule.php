<?php
/**
 * 契约，验证规则接口
 */

namespace Illuminate\Contracts\Validation;

interface Rule
{
    /**
     * Determine if the validation rule passes.
	 * 确定验证规则是否通过
     *
     * @param  string  $attribute
     * @param  mixed  $value
     * @return bool
     */
    public function passes($attribute, $value);

    /**
     * Get the validation error message.
	 * 得到验证错误消息
     *
     * @return string|array
     */
    public function message();
}

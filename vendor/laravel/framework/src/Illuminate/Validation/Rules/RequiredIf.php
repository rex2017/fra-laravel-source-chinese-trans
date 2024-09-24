<?php
/**
 * 验证，如果需要
 */

namespace Illuminate\Validation\Rules;

use InvalidArgumentException;

class RequiredIf
{
    /**
     * The condition that validates the attribute.
	 * 验证属性的条件
     *
     * @var callable|bool
     */
    public $condition;

    /**
     * Create a new required validation rule based on a condition.
	 * 创建新的所需验证规则根据条件
     *
     * @param  callable|bool  $condition
     * @return void
     */
    public function __construct($condition)
    {
        if (! is_string($condition)) {
            $this->condition = $condition;
        } else {
            throw new InvalidArgumentException('The provided condition must be a callable or boolean.');
        }
    }

    /**
     * Convert the rule to a validation string.
	 * 转换规则为验证字符串
     *
     * @return string
     */
    public function __toString()
    {
        if (is_callable($this->condition)) {
            return call_user_func($this->condition) ? 'required' : '';
        }

        return $this->condition ? 'required' : '';
    }
}

<?php
/**
 * 验证，不在
 */

namespace Illuminate\Validation\Rules;

class NotIn
{
    /**
     * The name of the rule.
	 * 规则名
     */
    protected $rule = 'not_in';

    /**
     * The accepted values.
	 * 可接受值
     *
     * @var array
     */
    protected $values;

    /**
     * Create a new "not in" rule instance.
	 * 创建新的规则实例
     *
     * @param  array  $values
     * @return void
     */
    public function __construct(array $values)
    {
        $this->values = $values;
    }

    /**
     * Convert the rule to a validation string.
	 * 转换规则为验证字符串
     *
     * @return string
     */
    public function __toString()
    {
        $values = array_map(function ($value) {
            return '"'.str_replace('"', '""', $value).'"';
        }, $this->values);

        return $this->rule.':'.implode(',', $values);
    }
}

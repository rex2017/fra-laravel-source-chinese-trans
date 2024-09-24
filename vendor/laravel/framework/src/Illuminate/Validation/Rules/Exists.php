<?php
/**
 * 验证，存在
 */

namespace Illuminate\Validation\Rules;

class Exists
{
    use DatabaseRule;

    /**
     * Convert the rule to a validation string.
	 * 转换规则为可验证字符串
     *
     * @return string
     */
    public function __toString()
    {
        return rtrim(sprintf('exists:%s,%s,%s',
            $this->table,
            $this->column,
            $this->formatWheres()
        ), ',');
    }
}

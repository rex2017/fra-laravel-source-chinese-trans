<?php
/**
 * 基础，Http中间件，转换空字符串
 */

namespace Illuminate\Foundation\Http\Middleware;

class ConvertEmptyStringsToNull extends TransformsRequest
{
    /**
     * Transform the given value.
	 * 转换给定值
     *
     * @param  string  $key
     * @param  mixed  $value
     * @return mixed
     */
    protected function transform($key, $value)
    {
        return is_string($value) && $value === '' ? null : $value;
    }
}

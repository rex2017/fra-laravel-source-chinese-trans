<?php
/**
 * 基础，Http中间件，修整字符串
 */

namespace Illuminate\Foundation\Http\Middleware;

class TrimStrings extends TransformsRequest
{
    /**
     * The attributes that should not be trimmed.
	 * 不应该被修剪的属性
     *
     * @var array
     */
    protected $except = [
        //
    ];

    /**
     * Transform the given value.
	 * 转换给定的值
     *
     * @param  string  $key
     * @param  mixed  $value
     * @return mixed
     */
    protected function transform($key, $value)
    {
        if (in_array($key, $this->except, true)) {
            return $value;
        }

        return is_string($value) ? trim($value) : $value;
    }
}

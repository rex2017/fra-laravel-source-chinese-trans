<?php
/**
 * 支持，门面验证
 */

namespace Illuminate\Support\Facades;

/**
 * @method static \Illuminate\Contracts\Validation\Validator make(array $data, array $rules, array $messages = [], array $customAttributes = [])
 * @method static void extend(string $rule, \Closure|string $extension, string $message = null)
 * @method static void extendImplicit(string $rule, \Closure|string $extension, string $message = null)
 * @method static void replacer(string $rule, \Closure|string $replacer)
 *
 * @see \Illuminate\Validation\Factory
 */
class Validator extends Facade
{
    /**
     * Get the registered name of the component.
	 * 得到组件注册名
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return 'validator';
    }
}

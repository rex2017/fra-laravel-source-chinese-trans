<?php
/**
 * 容器工具包
 */

namespace Illuminate\Container;

use Closure;
use ReflectionNamedType;

class Util
{
    /**
     * If the given value is not an array and not null, wrap it in one.
	 * 如果给定的值不是数组也不为空，则将其封装在一个数组中
     *
     * From Arr::wrap() in Illuminate\Support.
     *
     * @param  mixed  $value
     * @return array
     */
    public static function arrayWrap($value)
    {
        if (is_null($value)) {
            return [];
        }

        return is_array($value) ? $value : [$value];
    }

    /**
     * Return the default value of the given value.
	 * 返回给定值的默认值
     *
     * From global value() helper in Illuminate\Support.
     *
     * @param  mixed  $value
     * @return mixed
     */
    public static function unwrapIfClosure($value)
    {
        return $value instanceof Closure ? $value() : $value;
    }

    /**
     * Get the class name of the given parameter's type, if possible.
	 * 得到给定参数类型的类名
     *
     * From Reflector::getParameterClassName() in Illuminate\Support.
     *
     * @param  \ReflectionParameter  $parameter
     * @return string|null
     */
    public static function getParameterClassName($parameter)
    {
        $type = $parameter->getType();

        if (! $type instanceof ReflectionNamedType || $type->isBuiltin()) {
            return;
        }

        $name = $type->getName();

        if (! is_null($class = $parameter->getDeclaringClass())) {
            if ($name === 'self') {
                return $class->getName();
            }

            if ($name === 'parent' && $parent = $class->getParentClass()) {
                return $parent->getName();
            }
        }

        return $name;
    }
}

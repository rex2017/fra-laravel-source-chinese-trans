<?php
/**
 * 验证规则
 */

namespace Illuminate\Validation;

use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Support\Traits\Macroable;
use Illuminate\Validation\Rules\Dimensions;
use Illuminate\Validation\Rules\Exists;
use Illuminate\Validation\Rules\In;
use Illuminate\Validation\Rules\NotIn;
use Illuminate\Validation\Rules\RequiredIf;
use Illuminate\Validation\Rules\Unique;

class Rule
{
    use Macroable;

    /**
     * Get a dimensions constraint builder instance.
	 * 得到维度约束构建器实例
     *
     * @param  array  $constraints
     * @return \Illuminate\Validation\Rules\Dimensions
     */
    public static function dimensions(array $constraints = [])
    {
        return new Dimensions($constraints);
    }

    /**
     * Get a exists constraint builder instance.
	 * 得到一个已存在的约束生成器实例
     *
     * @param  string  $table
     * @param  string  $column
     * @return \Illuminate\Validation\Rules\Exists
     */
    public static function exists($table, $column = 'NULL')
    {
        return new Exists($table, $column);
    }

    /**
     * Get an in constraint builder instance.
	 * 得到一个约束生成器实例
     *
     * @param  \Illuminate\Contracts\Support\Arrayable|array|string  $values
     * @return \Illuminate\Validation\Rules\In
     */
    public static function in($values)
    {
        if ($values instanceof Arrayable) {
            $values = $values->toArray();
        }

        return new In(is_array($values) ? $values : func_get_args());
    }

    /**
     * Get a not_in constraint builder instance.
	 * 得到一个not_in约束生成器实例
     *
     * @param  \Illuminate\Contracts\Support\Arrayable|array|string  $values
     * @return \Illuminate\Validation\Rules\NotIn
     */
    public static function notIn($values)
    {
        if ($values instanceof Arrayable) {
            $values = $values->toArray();
        }

        return new NotIn(is_array($values) ? $values : func_get_args());
    }

    /**
     * Get a required_if constraint builder instance.
	 * 得到required_if约束构建器实例
     *
     * @param  callable|bool  $callback
     * @return \Illuminate\Validation\Rules\RequiredIf
     */
    public static function requiredIf($callback)
    {
        return new RequiredIf($callback);
    }

    /**
     * Get a unique constraint builder instance.
	 * 得到唯一约束生成器实例
     *
     * @param  string  $table
     * @param  string  $column
     * @return \Illuminate\Validation\Rules\Unique
     */
    public static function unique($table, $column = 'NULL')
    {
        return new Unique($table, $column);
    }
}

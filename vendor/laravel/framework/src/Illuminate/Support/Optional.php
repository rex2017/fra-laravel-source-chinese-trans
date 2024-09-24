<?php
/**
 * 支持，可选项
 */

namespace Illuminate\Support;

use ArrayAccess;
use ArrayObject;

class Optional implements ArrayAccess
{
    use Traits\Macroable {
        __call as macroCall;
    }

    /**
     * The underlying object.
	 * 底层对象
     *
     * @var mixed
     */
    protected $value;

    /**
     * Create a new optional instance.
	 * 创建新的选项实例
     *
     * @param  mixed  $value
     * @return void
     */
    public function __construct($value)
    {
        $this->value = $value;
    }

    /**
     * Dynamically access a property on the underlying object.
	 * 动态访问基础对象上的属性
     *
     * @param  string  $key
     * @return mixed
     */
    public function __get($key)
    {
        if (is_object($this->value)) {
            return $this->value->{$key} ?? null;
        }
    }

    /**
     * Dynamically check a property exists on the underlying object.
	 * 动态检查基础对象上是否存在属性
     *
     * @param  mixed  $name
     * @return bool
     */
    public function __isset($name)
    {
        if (is_object($this->value)) {
            return isset($this->value->{$name});
        }

        if (is_array($this->value) || $this->value instanceof ArrayObject) {
            return isset($this->value[$name]);
        }

        return false;
    }

    /**
     * Determine if an item exists at an offset.
	 * 确定某项是否存在于偏移量处
     *
     * @param  mixed  $key
     * @return bool
     */
    public function offsetExists($key)
    {
        return Arr::accessible($this->value) && Arr::exists($this->value, $key);
    }

    /**
     * Get an item at a given offset.
	 * 得到项在给定偏移量处
     *
     * @param  mixed  $key
     * @return mixed
     */
    public function offsetGet($key)
    {
        return Arr::get($this->value, $key);
    }

    /**
     * Set the item at a given offset.
	 * 设置项在给定的偏移量处
     *
     * @param  mixed  $key
     * @param  mixed  $value
     * @return void
     */
    public function offsetSet($key, $value)
    {
        if (Arr::accessible($this->value)) {
            $this->value[$key] = $value;
        }
    }

    /**
     * Unset the item at a given offset.
	 * 取消项的设置在给定的偏移量
     *
     * @param  string  $key
     * @return void
     */
    public function offsetUnset($key)
    {
        if (Arr::accessible($this->value)) {
            unset($this->value[$key]);
        }
    }

    /**
     * Dynamically pass a method to the underlying object.
	 * 动态地传递方法给底层对象
     *
     * @param  string  $method
     * @param  array  $parameters
     * @return mixed
     */
    public function __call($method, $parameters)
    {
        if (static::hasMacro($method)) {
            return $this->macroCall($method, $parameters);
        }

        if (is_object($this->value)) {
            return $this->value->{$method}(...$parameters);
        }
    }
}

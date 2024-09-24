<?php
/**
 * Http，委托至资源
 */

namespace Illuminate\Http\Resources;

use Exception;
use Illuminate\Support\Traits\ForwardsCalls;

trait DelegatesToResource
{
    use ForwardsCalls;

    /**
     * Get the value of the resource's route key.
	 * 得到资源的路由键的值
     *
     * @return mixed
     */
    public function getRouteKey()
    {
        return $this->resource->getRouteKey();
    }

    /**
     * Get the route key for the resource.
	 * 得到资源的路由键
     *
     * @return string
     */
    public function getRouteKeyName()
    {
        return $this->resource->getRouteKeyName();
    }

    /**
     * Retrieve the model for a bound value.
	 * 检索绑定值的模型
     *
     * @param  mixed  $value
     * @return void
     *
     * @throws \Exception
     */
    public function resolveRouteBinding($value)
    {
        throw new Exception('Resources may not be implicitly resolved from route bindings.');
    }

    /**
     * Determine if the given attribute exists.
	 * 确定给定属性是否存在
     *
     * @param  mixed  $offset
     * @return bool
     */
    public function offsetExists($offset)
    {
        return isset($this->resource[$offset]);
    }

    /**
     * Get the value for a given offset.
	 * 得到给定偏移量的值
     *
     * @param  mixed  $offset
     * @return mixed
     */
    public function offsetGet($offset)
    {
        return $this->resource[$offset];
    }

    /**
     * Set the value for a given offset.
	 * 设置给定偏移量的值
     *
     * @param  mixed  $offset
     * @param  mixed  $value
     * @return void
     */
    public function offsetSet($offset, $value)
    {
        $this->resource[$offset] = $value;
    }

    /**
     * Unset the value for a given offset.
	 * 取消给定偏移量的值
     *
     * @param  mixed  $offset
     * @return void
     */
    public function offsetUnset($offset)
    {
        unset($this->resource[$offset]);
    }

    /**
     * Determine if an attribute exists on the resource.
	 * 确定资源上是否存在属性
     *
     * @param  string  $key
     * @return bool
     */
    public function __isset($key)
    {
        return isset($this->resource->{$key});
    }

    /**
     * Unset an attribute on the resource.
	 * 取消对资源的属性设置
     *
     * @param  string  $key
     * @return void
     */
    public function __unset($key)
    {
        unset($this->resource->{$key});
    }

    /**
     * Dynamically get properties from the underlying resource.
	 * 动态获取属性从底层资源
     *
     * @param  string  $key
     * @return mixed
     */
    public function __get($key)
    {
        return $this->resource->{$key};
    }

    /**
     * Dynamically pass method calls to the underlying resource.
	 * 动态调取方法从底层资源
     *
     * @param  string  $method
     * @param  array  $parameters
     * @return mixed
     */
    public function __call($method, $parameters)
    {
        return $this->forwardCallTo($this->resource, $method, $parameters);
    }
}

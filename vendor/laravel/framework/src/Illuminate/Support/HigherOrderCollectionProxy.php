<?php
/**
 * 支持，高阶集合代理
 */

namespace Illuminate\Support;

/**
 * @mixin \Illuminate\Support\Enumerable
 */
class HigherOrderCollectionProxy
{
    /**
     * The collection being operated on.
	 * 正在操作的集合
     *
     * @var \Illuminate\Support\Enumerable
     */
    protected $collection;

    /**
     * The method being proxied.
	 * 被代理的方法
     *
     * @var string
     */
    protected $method;

    /**
     * Create a new proxy instance.
	 * 创建新的代码实例
     *
     * @param  \Illuminate\Support\Enumerable  $collection
     * @param  string  $method
     * @return void
     */
    public function __construct(Enumerable $collection, $method)
    {
        $this->method = $method;
        $this->collection = $collection;
    }

    /**
     * Proxy accessing an attribute onto the collection items.
	 * 代理访问集合项上的属性
     *
     * @param  string  $key
     * @return mixed
     */
    public function __get($key)
    {
        return $this->collection->{$this->method}(function ($value) use ($key) {
            return is_array($value) ? $value[$key] : $value->{$key};
        });
    }

    /**
     * Proxy a method call onto the collection items.
	 * 将方法调用代理到集合项上
     *
     * @param  string  $method
     * @param  array  $parameters
     * @return mixed
     */
    public function __call($method, $parameters)
    {
        return $this->collection->{$this->method}(function ($value) use ($method, $parameters) {
            return $value->{$method}(...$parameters);
        });
    }
}

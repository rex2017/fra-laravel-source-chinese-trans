<?php
/**
 * 支持，查看错误包
 */

namespace Illuminate\Support;

use Countable;
use Illuminate\Contracts\Support\MessageBag as MessageBagContract;

/**
 * @mixin \Illuminate\Contracts\Support\MessageBag
 */
class ViewErrorBag implements Countable
{
    /**
     * The array of the view error bags.
	 * 视图错误包的数组
     *
     * @var array
     */
    protected $bags = [];

    /**
     * Checks if a named MessageBag exists in the bags.
	 * 检查包中是否存在一个命名的MessageBag
     *
     * @param  string  $key
     * @return bool
     */
    public function hasBag($key = 'default')
    {
        return isset($this->bags[$key]);
    }

    /**
     * Get a MessageBag instance from the bags.
	 * 得到MessageBag实例从包中
     *
     * @param  string  $key
     * @return \Illuminate\Contracts\Support\MessageBag
     */
    public function getBag($key)
    {
        return Arr::get($this->bags, $key) ?: new MessageBag;
    }

    /**
     * Get all the bags.
	 * 得到所有的包
     *
     * @return array
     */
    public function getBags()
    {
        return $this->bags;
    }

    /**
     * Add a new MessageBag instance to the bags.
	 * 添加一个新的MessageBag实例向包中
     *
     * @param  string  $key
     * @param  \Illuminate\Contracts\Support\MessageBag  $bag
     * @return $this
     */
    public function put($key, MessageBagContract $bag)
    {
        $this->bags[$key] = $bag;

        return $this;
    }

    /**
     * Determine if the default message bag has any messages.
	 * 确定默认消息包是否包含任何消息
     *
     * @return bool
     */
    public function any()
    {
        return $this->count() > 0;
    }

    /**
     * Get the number of messages in the default bag.
	 * 得到默认包中的消息数
     *
     * @return int
     */
    public function count()
    {
        return $this->getBag('default')->count();
    }

    /**
     * Dynamically call methods on the default bag.
	 * 动态调用默认包上的方法
     *
     * @param  string  $method
     * @param  array  $parameters
     * @return mixed
     */
    public function __call($method, $parameters)
    {
        return $this->getBag('default')->$method(...$parameters);
    }

    /**
     * Dynamically access a view error bag.
	 * 动态访问视图错误包
     *
     * @param  string  $key
     * @return \Illuminate\Contracts\Support\MessageBag
     */
    public function __get($key)
    {
        return $this->getBag($key);
    }

    /**
     * Dynamically set a view error bag.
	 * 动态设置视图错误包
     *
     * @param  string  $key
     * @param  \Illuminate\Contracts\Support\MessageBag  $value
     * @return void
     */
    public function __set($key, $value)
    {
        $this->put($key, $value);
    }

    /**
     * Convert the default bag to its string representation.
	 * 转换默认包为其字符串表示形式
     *
     * @return string
     */
    public function __toString()
    {
        return (string) $this->getBag('default');
    }
}

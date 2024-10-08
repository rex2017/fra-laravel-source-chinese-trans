<?php
/**
 * 缓存空存储
 */

namespace Illuminate\Cache;

class NullStore extends TaggableStore
{
    use RetrievesMultipleKeys;

    /**
     * Retrieve an item from the cache by key.
	 * 检索项目从缓存中
     *
     * @param  string  $key
     * @return mixed
     */
    public function get($key)
    {
        //
    }

    /**
     * Store an item in the cache for a given number of seconds.
	 * 存储项目在缓存中使用给定的秒数
     *
     * @param  string  $key
     * @param  mixed  $value
     * @param  int  $seconds
     * @return bool
     */
    public function put($key, $value, $seconds)
    {
        return false;
    }

    /**
     * Increment the value of an item in the cache.
	 * 增加缓存中项的值
     *
     * @param  string  $key
     * @param  mixed  $value
     * @return int|bool
     */
    public function increment($key, $value = 1)
    {
        return false;
    }

    /**
     * Decrement the value of an item in the cache.
	 * 递减缓存中项的值
     *
     * @param  string  $key
     * @param  mixed  $value
     * @return int|bool
     */
    public function decrement($key, $value = 1)
    {
        return false;
    }

    /**
     * Store an item in the cache indefinitely.
	 * 存储项目无限期地在缓存中
     *
     * @param  string  $key
     * @param  mixed  $value
     * @return bool
     */
    public function forever($key, $value)
    {
        return false;
    }

    /**
     * Remove an item from the cache.
	 * 从缓存中删除项目
     *
     * @param  string  $key
     * @return bool
     */
    public function forget($key)
    {
        return true;
    }

    /**
     * Remove all items from the cache.
	 * 清空缓存中所有项目
     *
     * @return bool
     */
    public function flush()
    {
        return true;
    }

    /**
     * Get the cache key prefix.
	 * 得到缓存前缀
     *
     * @return string
     */
    public function getPrefix()
    {
        return '';
    }
}

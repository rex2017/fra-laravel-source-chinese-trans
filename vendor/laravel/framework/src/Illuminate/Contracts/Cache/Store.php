<?php
/**
 * 契约，缓存存储接口
 */

namespace Illuminate\Contracts\Cache;

interface Store
{
    /**
     * Retrieve an item from the cache by key.
	 * 检索一个项从缓存中
     *
     * @param  string|array  $key
     * @return mixed
     */
    public function get($key);

    /**
     * Retrieve multiple items from the cache by key.
	 * 检索多个项按键从缓存中
     *
     * Items not found in the cache will have a null value.
     *
     * @param  array  $keys
     * @return array
     */
    public function many(array $keys);

    /**
     * Store an item in the cache for a given number of seconds.
	 * 存储项在缓存中给定的秒数
     *
     * @param  string  $key
     * @param  mixed  $value
     * @param  int  $seconds
     * @return bool
     */
    public function put($key, $value, $seconds);

    /**
     * Store multiple items in the cache for a given number of seconds.
	 * 存储多个项在缓存中在给定的秒数
     *
     * @param  array  $values
     * @param  int  $seconds
     * @return bool
     */
    public function putMany(array $values, $seconds);

    /**
     * Increment the value of an item in the cache.
	 * 增加缓存中项的值
     *
     * @param  string  $key
     * @param  mixed  $value
     * @return int|bool
     */
    public function increment($key, $value = 1);

    /**
     * Decrement the value of an item in the cache.
	 * 递减缓存中项的值
     *
     * @param  string  $key
     * @param  mixed  $value
     * @return int|bool
     */
    public function decrement($key, $value = 1);

    /**
     * Store an item in the cache indefinitely.
	 * 存储项在缓存中无限期
     *
     * @param  string  $key
     * @param  mixed  $value
     * @return bool
     */
    public function forever($key, $value);

    /**
     * Remove an item from the cache.
	 * 删除项从缓存中
     *
     * @param  string  $key
     * @return bool
     */
    public function forget($key);

    /**
     * Remove all items from the cache.
	 * 删除所有项从缓存中
     *
     * @return bool
     */
    public function flush();

    /**
     * Get the cache key prefix.
	 * 得到缓存键前缀
     *
     * @return string
     */
    public function getPrefix();
}

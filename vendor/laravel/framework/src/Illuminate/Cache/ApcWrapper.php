<?php
/**
 * 缓存，APC封装器
 */

namespace Illuminate\Cache;

class ApcWrapper
{
    /**
     * Indicates if APCu is supported.
	 * 指明是否APCu是否提供
     *
     * @var bool
     */
    protected $apcu = false;

    /**
     * Create a new APC wrapper instance.
	 * 创建新的APC封装实例
     *
     * @return void
     */
    public function __construct()
    {
        $this->apcu = function_exists('apcu_fetch');
    }

    /**
     * Get an item from the cache.
	 * 得到一个项目从缓存中
     *
     * @param  string  $key
     * @return mixed
     */
    public function get($key)
    {
        return $this->apcu ? apcu_fetch($key) : apc_fetch($key);
    }

    /**
     * Store an item in the cache.
	 * 缓存一个项目至缓存中
     *
     * @param  string  $key
     * @param  mixed  $value
     * @param  int  $seconds
     * @return array|bool
     */
    public function put($key, $value, $seconds)
    {
        return $this->apcu ? apcu_store($key, $value, $seconds) : apc_store($key, $value, $seconds);
    }

    /**
     * Increment the value of an item in the cache.
	 * 增加缓存中项的值
     *
     * @param  string  $key
     * @param  mixed  $value
     * @return int|bool
     */
    public function increment($key, $value)
    {
        return $this->apcu ? apcu_inc($key, $value) : apc_inc($key, $value);
    }

    /**
     * Decrement the value of an item in the cache.
	 * 递减缓存中项目的值
     *
     * @param  string  $key
     * @param  mixed  $value
     * @return int|bool
     */
    public function decrement($key, $value)
    {
        return $this->apcu ? apcu_dec($key, $value) : apc_dec($key, $value);
    }

    /**
     * Remove an item from the cache.
	 * 从缓存中移除项
     *
     * @param  string  $key
     * @return bool
     */
    public function delete($key)
    {
        return $this->apcu ? apcu_delete($key) : apc_delete($key);
    }

    /**
     * Remove all items from the cache.
	 * 清空缓存
     *
     * @return bool
     */
    public function flush()
    {
        return $this->apcu ? apcu_clear_cache() : apc_clear_cache('user');
    }
}

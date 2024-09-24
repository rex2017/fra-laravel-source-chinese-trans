<?php
/**
 * 契约，缓存资源库
 */

namespace Illuminate\Contracts\Cache;

use Closure;
use Psr\SimpleCache\CacheInterface;

interface Repository extends CacheInterface
{
    /**
     * Retrieve an item from the cache and delete it.
	 * 检索项从缓存中并删除它
     *
     * @param  string  $key
     * @param  mixed  $default
     * @return mixed
     */
    public function pull($key, $default = null);

    /**
     * Store an item in the cache.
	 * 存储一个项至缓存里
     *
     * @param  string  $key
     * @param  mixed  $value
     * @param  \DateTimeInterface|\DateInterval|int|null  $ttl
     * @return bool
     */
    public function put($key, $value, $ttl = null);

    /**
     * Store an item in the cache if the key does not exist.
	 * 添加一个项至缓存里
     *
     * @param  string  $key
     * @param  mixed  $value
     * @param  \DateTimeInterface|\DateInterval|int|null  $ttl
     * @return bool
     */
    public function add($key, $value, $ttl = null);

    /**
     * Increment the value of an item in the cache.
	 * 增加值
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
	 * 保存缓存永久
     *
     * @param  string  $key
     * @param  mixed  $value
     * @return bool
     */
    public function forever($key, $value);

    /**
     * Get an item from the cache, or execute the given Closure and store the result.
	 * 从缓存中获取一个项
     *
     * @param  string  $key
     * @param  \DateTimeInterface|\DateInterval|int|null  $ttl
     * @param  \Closure  $callback
     * @return mixed
     */
    public function remember($key, $ttl, Closure $callback);

    /**
     * Get an item from the cache, or execute the given Closure and store the result forever.
	 * 从缓存中获取一个项，或者执行给定的Closure并永久存储结果。
     *
     * @param  string  $key
     * @param  \Closure  $callback
     * @return mixed
     */
    public function sear($key, Closure $callback);

    /**
     * Get an item from the cache, or execute the given Closure and store the result forever.
	 * 从缓存中获取一个项，或者执行给定的Closure并永久存储结果。
     *
     * @param  string  $key
     * @param  \Closure  $callback
     * @return mixed
     */
    public function rememberForever($key, Closure $callback);

    /**
     * Remove an item from the cache.
	 * 注册缓存项
     *
     * @param  string  $key
     * @return bool
     */
    public function forget($key);

    /**
     * Get the cache store implementation.
	 * 得到缓存存储实现
     *
     * @return \Illuminate\Contracts\Cache\Store
     */
    public function getStore();
}

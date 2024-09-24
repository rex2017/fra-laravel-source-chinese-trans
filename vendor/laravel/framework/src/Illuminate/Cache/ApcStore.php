<?php
/**
 * Apc缓存，Alternatice PHP Cache，可选PHP缓存
 */

namespace Illuminate\Cache;

class ApcStore extends TaggableStore
{
    use RetrievesMultipleKeys;

    /**
     * The APC wrapper instance.
	 * apc封装实例
     *
     * @var \Illuminate\Cache\ApcWrapper
     */
    protected $apc;

    /**
     * A string that should be prepended to keys.
	 * 前缀，应该加在键前的字符串
     *
     * @var string
     */
    protected $prefix;

    /**
     * Create a new APC store.
	 * 创建新的apc存储
     *
     * @param  \Illuminate\Cache\ApcWrapper  $apc
     * @param  string  $prefix
     * @return void
     */
    public function __construct(ApcWrapper $apc, $prefix = '')
    {
        $this->apc = $apc;
        $this->prefix = $prefix;
    }

    /**
     * Retrieve an item from the cache by key.
	 * 检索一个项目从cache
     *
     * @param  string|array  $key
     * @return mixed
     */
    public function get($key)
    {
        $value = $this->apc->get($this->prefix.$key);

        if ($value !== false) {
            return $value;
        }
    }

    /**
     * Store an item in the cache for a given number of seconds.
	 * 存储一个项目入缓存中使用给定秒数
     *
     * @param  string  $key
     * @param  mixed  $value
     * @param  int  $seconds
     * @return bool
     */
    public function put($key, $value, $seconds)
    {
        return $this->apc->put($this->prefix.$key, $value, $seconds);
    }

    /**
     * Increment the value of an item in the cache.
	 * 增加缓存中的值
     *
     * @param  string  $key
     * @param  mixed  $value
     * @return int|bool
     */
    public function increment($key, $value = 1)
    {
        return $this->apc->increment($this->prefix.$key, $value);
    }

    /**
     * Decrement the value of an item in the cache.
	 * 递减缓存中项目的值
     *
     * @param  string  $key
     * @param  mixed  $value
     * @return int|bool
     */
    public function decrement($key, $value = 1)
    {
        return $this->apc->decrement($this->prefix.$key, $value);
    }

    /**
     * Store an item in the cache indefinitely.
	 * 存储一个项目无限期在缓存中
     *
     * @param  string  $key
     * @param  mixed  $value
     * @return bool
     */
    public function forever($key, $value)
    {
        return $this->put($key, $value, 0);
    }

    /**
     * Remove an item from the cache.
	 * 移除项目从缓存中
     *
     * @param  string  $key
     * @return bool
     */
    public function forget($key)
    {
        return $this->apc->delete($this->prefix.$key);
    }

    /**
     * Remove all items from the cache.
	 * 移除所有项目从缓存中
     *
     * @return bool
     */
    public function flush()
    {
        return $this->apc->flush();
    }

    /**
     * Get the cache key prefix.
	 * 得到缓存前缀
     *
     * @return string
     */
    public function getPrefix()
    {
        return $this->prefix;
    }
}

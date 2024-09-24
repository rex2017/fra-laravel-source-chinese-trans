<?php
/**
 * 缓存数组存储
 */

namespace Illuminate\Cache;

use Illuminate\Contracts\Cache\LockProvider;
use Illuminate\Support\InteractsWithTime;

class ArrayStore extends TaggableStore implements LockProvider
{
    use InteractsWithTime, RetrievesMultipleKeys;

    /**
     * The array of stored values.
	 * 存储值数组
     *
     * @var array
     */
    protected $storage = [];

    /**
     * The array of locks.
	 * 锁定数组
     *
     * @var array
     */
    public $locks = [];

    /**
     * Retrieve an item from the cache by key.
	 * 检索一个项目从cache中
     *
     * @param  string|array  $key
     * @return mixed
     */
    public function get($key)
    {
        if (! isset($this->storage[$key])) {
            return;
        }

        $item = $this->storage[$key];

        $expiresAt = $item['expiresAt'] ?? 0;

        if ($expiresAt !== 0 && $this->currentTime() > $expiresAt) {
            $this->forget($key);

            return;
        }

        return $item['value'];
    }

    /**
     * Store an item in the cache for a given number of seconds.
	 * 存储一个项目至缓存中使用给定秒数
     *
     * @param  string  $key
     * @param  mixed  $value
     * @param  int  $seconds
     * @return bool
     */
    public function put($key, $value, $seconds)
    {
        $this->storage[$key] = [
            'value' => $value,
            'expiresAt' => $this->calculateExpiration($seconds),
        ];

        return true;
    }

    /**
     * Increment the value of an item in the cache.
	 * 增加缓存中某个项
     *
     * @param  string  $key
     * @param  mixed  $value
     * @return int
     */
    public function increment($key, $value = 1)
    {
        if (! isset($this->storage[$key])) {
            $this->forever($key, $value);

            return $this->storage[$key]['value'];
        }

        $this->storage[$key]['value'] = ((int) $this->storage[$key]['value']) + $value;

        return $this->storage[$key]['value'];
    }

    /**
     * Decrement the value of an item in the cache.
	 * 递减缓存中项目的值
     *
     * @param  string  $key
     * @param  mixed  $value
     * @return int
     */
    public function decrement($key, $value = 1)
    {
        return $this->increment($key, $value * -1);
    }

    /**
     * Store an item in the cache indefinitely.
	 * 将项无限期地存储在缓存中
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
	 * 移除一条
     *
     * @param  string  $key
     * @return bool
     */
    public function forget($key)
    {
        if (array_key_exists($key, $this->storage)) {
            unset($this->storage[$key]);

            return true;
        }

        return false;
    }

    /**
     * Remove all items from the cache.
	 * 移除所有
     *
     * @return bool
     */
    public function flush()
    {
        $this->storage = [];

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

    /**
     * Get the expiration time of the key.
	 * 得到密钥的过期时间
     *
     * @param  int  $seconds
     * @return int
     */
    protected function calculateExpiration($seconds)
    {
        return $this->toTimestamp($seconds);
    }

    /**
     * Get the UNIX timestamp for the given number of seconds.
	 * 得到给定秒数的UNIX时间戳
     *
     * @param  int  $seconds
     * @return int
     */
    protected function toTimestamp($seconds)
    {
        return $seconds > 0 ? $this->availableAt($seconds) : 0;
    }

    /**
     * Get a lock instance.
	 * 得到锁实例
     *
     * @param  string  $name
     * @param  int  $seconds
     * @param  string|null  $owner
     * @return \Illuminate\Contracts\Cache\Lock
     */
    public function lock($name, $seconds = 0, $owner = null)
    {
        return new ArrayLock($this, $name, $seconds, $owner);
    }

    /**
     * Restore a lock instance using the owner identifier.
	 * 恢复锁实例使用所有者标识符
	 * 
     *
     * @param  string  $name
     * @param  string  $owner
     * @return \Illuminate\Contracts\Cache\Lock
     */
    public function restoreLock($name, $owner)
    {
        return $this->lock($name, 0, $owner);
    }
}

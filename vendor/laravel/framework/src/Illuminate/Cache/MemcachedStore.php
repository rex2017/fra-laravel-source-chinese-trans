<?php
/**
 * 缓存Memcached存储
 */

namespace Illuminate\Cache;

use Illuminate\Contracts\Cache\LockProvider;
use Illuminate\Support\InteractsWithTime;
use Memcached;
use ReflectionMethod;

class MemcachedStore extends TaggableStore implements LockProvider
{
    use InteractsWithTime;

    /**
     * The Memcached instance.
	 * Memcached实例
     *
     * @var \Memcached
     */
    protected $memcached;

    /**
     * A string that should be prepended to keys.
	 * 前缀，应该加在键前的字符串
     *
     * @var string
     */
    protected $prefix;

    /**
     * Indicates whether we are using Memcached version >= 3.0.0.
	 * 指明我们是否使用Memcached版本>= 3.0.0
     *
     * @var bool
     */
    protected $onVersionThree;

    /**
     * Create a new Memcached store.
	 * 创建新的Memcached存储
     *
     * @param  \Memcached  $memcached
     * @param  string  $prefix
     * @return void
     */
    public function __construct($memcached, $prefix = '')
    {
        $this->setPrefix($prefix);
        $this->memcached = $memcached;

        $this->onVersionThree = (new ReflectionMethod('Memcached', 'getMulti'))
                            ->getNumberOfParameters() == 2;
    }

    /**
     * Retrieve an item from the cache by key.
	 * 检索项目从缓存
     *
     * @param  string  $key
     * @return mixed
     */
    public function get($key)
    {
        $value = $this->memcached->get($this->prefix.$key);

        if ($this->memcached->getResultCode() == 0) {
            return $value;
        }
    }

    /**
     * Retrieve multiple items from the cache by key.
	 * 检索多个项目从缓存
     *
     * Items not found in the cache will have a null value.
     *
     * @param  array  $keys
     * @return array
     */
    public function many(array $keys)
    {
        $prefixedKeys = array_map(function ($key) {
            return $this->prefix.$key;
        }, $keys);

        if ($this->onVersionThree) {
            $values = $this->memcached->getMulti($prefixedKeys, Memcached::GET_PRESERVE_ORDER);
        } else {
            $null = null;

            $values = $this->memcached->getMulti($prefixedKeys, $null, Memcached::GET_PRESERVE_ORDER);
        }

        if ($this->memcached->getResultCode() != 0) {
            return array_fill_keys($keys, null);
        }

        return array_combine($keys, $values);
    }

    /**
     * Store an item in the cache for a given number of seconds.
	 * 存储项目在缓存中给定的秒数
     *
     * @param  string  $key
     * @param  mixed  $value
     * @param  int  $seconds
     * @return bool
     */
    public function put($key, $value, $seconds)
    {
        return $this->memcached->set(
            $this->prefix.$key, $value, $this->calculateExpiration($seconds)
        );
    }

    /**
     * Store multiple items in the cache for a given number of seconds.
	 * 存储多个项目在缓存中使用给定的秒数
     *
     * @param  array  $values
     * @param  int  $seconds
     * @return bool
     */
    public function putMany(array $values, $seconds)
    {
        $prefixedValues = [];

        foreach ($values as $key => $value) {
            $prefixedValues[$this->prefix.$key] = $value;
        }

        return $this->memcached->setMulti(
            $prefixedValues, $this->calculateExpiration($seconds)
        );
    }

    /**
     * Store an item in the cache if the key doesn't exist.
	 * 存储项目在缓存中，如果键不存在。
     *
     * @param  string  $key
     * @param  mixed  $value
     * @param  int  $seconds
     * @return bool
     */
    public function add($key, $value, $seconds)
    {
        return $this->memcached->add(
            $this->prefix.$key, $value, $this->calculateExpiration($seconds)
        );
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
        return $this->memcached->increment($this->prefix.$key, $value);
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
        return $this->memcached->decrement($this->prefix.$key, $value);
    }

    /**
     * Store an item in the cache indefinitely.
	 * 忘记一个项目，存储项目无限期在缓存中
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
        return new MemcachedLock($this->memcached, $this->prefix.$name, $seconds, $owner);
    }

    /**
     * Restore a lock instance using the owner identifier.
	 * 恢复锁实例使用所有者标识符
     *
     * @param  string  $name
     * @param  string  $owner
     * @return \Illuminate\Contracts\Cache\Lock
     */
    public function restoreLock($name, $owner)
    {
        return $this->lock($name, 0, $owner);
    }

    /**
     * Remove an item from the cache.
	 * 移除一个项从缓存中
     *
     * @param  string  $key
     * @return bool
     */
    public function forget($key)
    {
        return $this->memcached->delete($this->prefix.$key);
    }

    /**
     * Remove all items from the cache.
	 * 移除所有项从缓存中
     *
     * @return bool
     */
    public function flush()
    {
        return $this->memcached->flush();
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
     * Get the underlying Memcached connection.
	 * 得到底层Memcached连接
     *
     * @return \Memcached
     */
    public function getMemcached()
    {
        return $this->memcached;
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

    /**
     * Set the cache key prefix.
	 * 设置缓存前缀
     *
     * @param  string  $prefix
     * @return void
     */
    public function setPrefix($prefix)
    {
        $this->prefix = ! empty($prefix) ? $prefix.':' : '';
    }
}

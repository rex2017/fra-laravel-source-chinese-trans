<?php
/**
 * Redis存储
 */

namespace Illuminate\Cache;

use Illuminate\Contracts\Cache\LockProvider;
use Illuminate\Contracts\Redis\Factory as Redis;

class RedisStore extends TaggableStore implements LockProvider
{
    /**
     * The Redis factory implementation.
	 * Redis工厂实现
     *
     * @var \Illuminate\Contracts\Redis\Factory
     */
    protected $redis;

    /**
     * A string that should be prepended to keys.
	 * 前缀，应该加在键前的字符串
     *
     * @var string
     */
    protected $prefix;

    /**
     * The Redis connection that should be used.
	 * 应该使用的Redis连接
     *
     * @var string
     */
    protected $connection;

    /**
     * Create a new Redis store.
	 * 创建新的Redis存储
     *
     * @param  \Illuminate\Contracts\Redis\Factory  $redis
     * @param  string  $prefix
     * @param  string  $connection
     * @return void
     */
    public function __construct(Redis $redis, $prefix = '', $connection = 'default')
    {
        $this->redis = $redis;
        $this->setPrefix($prefix);
        $this->setConnection($connection);
    }

    /**
     * Retrieve an item from the cache by key.
	 * 检索项目从缓存中
     *
     * @param  string|array  $key
     * @return mixed
     */
    public function get($key)
    {
        $value = $this->connection()->get($this->prefix.$key);

        return ! is_null($value) ? $this->unserialize($value) : null;
    }

    /**
     * Retrieve multiple items from the cache by key.
	 * 检索多个项目从缓存中
     *
     * Items not found in the cache will have a null value.
     *
     * @param  array  $keys
     * @return array
     */
    public function many(array $keys)
    {
        $results = [];

        $values = $this->connection()->mget(array_map(function ($key) {
            return $this->prefix.$key;
        }, $keys));

        foreach ($values as $index => $value) {
            $results[$keys[$index]] = ! is_null($value) ? $this->unserialize($value) : null;
        }

        return $results;
    }

    /**
     * Store an item in the cache for a given number of seconds.
	 * 存储一个项目在缓存中使用给定的秒数
     *
     * @param  string  $key
     * @param  mixed  $value
     * @param  int  $seconds
     * @return bool
     */
    public function put($key, $value, $seconds)
    {
        return (bool) $this->connection()->setex(
            $this->prefix.$key, (int) max(1, $seconds), $this->serialize($value)
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
        $this->connection()->multi();

        $manyResult = null;

        foreach ($values as $key => $value) {
            $result = $this->put($key, $value, $seconds);

            $manyResult = is_null($manyResult) ? $result : $result && $manyResult;
        }

        $this->connection()->exec();

        return $manyResult ?: false;
    }

    /**
     * Store an item in the cache if the key doesn't exist.
	 * 存储项目在缓存中，如果键不存在
     *
     * @param  string  $key
     * @param  mixed  $value
     * @param  int  $seconds
     * @return bool
     */
    public function add($key, $value, $seconds)
    {
        $lua = "return redis.call('exists',KEYS[1])<1 and redis.call('setex',KEYS[1],ARGV[2],ARGV[1])";

        return (bool) $this->connection()->eval(
            $lua, 1, $this->prefix.$key, $this->serialize($value), (int) max(1, $seconds)
        );
    }

    /**
     * Increment the value of an item in the cache.
	 * 增加缓存中项的值
     *
     * @param  string  $key
     * @param  mixed  $value
     * @return int
     */
    public function increment($key, $value = 1)
    {
        return $this->connection()->incrby($this->prefix.$key, $value);
    }

    /**
     * Decrement the value of an item in the cache.
	 * 递减缓存中项的值
     *
     * @param  string  $key
     * @param  mixed  $value
     * @return int
     */
    public function decrement($key, $value = 1)
    {
        return $this->connection()->decrby($this->prefix.$key, $value);
    }

    /**
     * Store an item in the cache indefinitely.
	 * 存储项目在缓存中无限期
     *
     * @param  string  $key
     * @param  mixed  $value
     * @return bool
     */
    public function forever($key, $value)
    {
        return (bool) $this->connection()->set($this->prefix.$key, $this->serialize($value));
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
        return new RedisLock($this->connection(), $this->prefix.$name, $seconds, $owner);
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
	 * 移除一项从缓存中
     *
     * @param  string  $key
     * @return bool
     */
    public function forget($key)
    {
        return (bool) $this->connection()->del($this->prefix.$key);
    }

    /**
     * Remove all items from the cache.
	 * 清空所有项从缓存
     *
     * @return bool
     */
    public function flush()
    {
        $this->connection()->flushdb();

        return true;
    }

    /**
     * Begin executing a new tags operation.
	 * 开始执行一个新的标记操作
     *
     * @param  array|mixed  $names
     * @return \Illuminate\Cache\RedisTaggedCache
     */
    public function tags($names)
    {
        return new RedisTaggedCache(
            $this, new TagSet($this, is_array($names) ? $names : func_get_args())
        );
    }

    /**
     * Get the Redis connection instance.
	 * 得到Redis连接实例
     *
     * @return \Illuminate\Redis\Connections\Connection
     */
    public function connection()
    {
        return $this->redis->connection($this->connection);
    }

    /**
     * Set the connection name to be used.
	 * 设置要使用的连接名称
     *
     * @param  string  $connection
     * @return void
     */
    public function setConnection($connection)
    {
        $this->connection = $connection;
    }

    /**
     * Get the Redis database instance.
	 * 得到Redis数据库实例
     *
     * @return \Illuminate\Contracts\Redis\Factory
     */
    public function getRedis()
    {
        return $this->redis;
    }

    /**
     * Get the cache key prefix.
	 * 得到缓存键前缀
     *
     * @return string
     */
    public function getPrefix()
    {
        return $this->prefix;
    }

    /**
     * Set the cache key prefix.
	 * 设置缓存键前缀
     *
     * @param  string  $prefix
     * @return void
     */
    public function setPrefix($prefix)
    {
        $this->prefix = ! empty($prefix) ? $prefix.':' : '';
    }

    /**
     * Serialize the value.
	 * 序列化值
     *
     * @param  mixed  $value
     * @return mixed
     */
    protected function serialize($value)
    {
        return is_numeric($value) && ! in_array($value, [INF, -INF]) && ! is_nan($value) ? $value : serialize($value);
    }

    /**
     * Unserialize the value.
	 * 反序列化值
     *
     * @param  mixed  $value
     * @return mixed
     */
    protected function unserialize($value)
    {
        return is_numeric($value) ? $value : unserialize($value);
    }
}

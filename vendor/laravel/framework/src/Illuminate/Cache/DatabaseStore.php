<?php
/**
 * 缓存数据库存储
 */

namespace Illuminate\Cache;

use Closure;
use Exception;
use Illuminate\Contracts\Cache\Store;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Database\PostgresConnection;
use Illuminate\Support\InteractsWithTime;
use Illuminate\Support\Str;

class DatabaseStore implements Store
{
    use InteractsWithTime, RetrievesMultipleKeys;

    /**
     * The database connection instance.
	 * 数据库连接实例
     *
     * @var \Illuminate\Database\ConnectionInterface
     */
    protected $connection;

    /**
     * The name of the cache table.
	 * 缓存表名
     *
     * @var string
     */
    protected $table;

    /**
     * A string that should be prepended to keys.
	 * 前缀，应该加在键前的字符串
     *
     * @var string
     */
    protected $prefix;

    /**
     * Create a new database store.
	 * 创建新的数据库存储
     *
     * @param  \Illuminate\Database\ConnectionInterface  $connection
     * @param  string  $table
     * @param  string  $prefix
     * @return void
     */
    public function __construct(ConnectionInterface $connection, $table, $prefix = '')
    {
        $this->table = $table;
        $this->prefix = $prefix;
        $this->connection = $connection;
    }

    /**
     * Retrieve an item from the cache by key.
	 * 检索一个项目从缓存中
     *
     * @param  string|array  $key
     * @return mixed
     */
    public function get($key)
    {
        $prefixed = $this->prefix.$key;

        $cache = $this->table()->where('key', '=', $prefixed)->first();

        // If we have a cache record we will check the expiration time against current
        // time on the system and see if the record has expired. If it has, we will
        // remove the records from the database table so it isn't returned again.
		// 如果我们有缓存记录，我们将检查超时时间，通过对比系统时间。
		// 如果记录已过期，我们将从数据表中删除记录，，这样它就不会再次返回。
        if (is_null($cache)) {
            return;
        }

        $cache = is_array($cache) ? (object) $cache : $cache;

        // If this cache expiration date is past the current time, we will remove this
        // item from the cache. Then we will return a null value since the cache is
        // expired. We will use "Carbon" to make this comparison with the column.
		// 如果此缓存过期日期超过当前时间，我们将删除这个项目从缓存中。
		// 然后这们将返回一个空值，因为缓存过期了。
		// 我们将使用"复写纸"与专栏进行比较。
        if ($this->currentTime() >= $cache->expiration) {
            $this->forget($key);

            return;
        }

        return $this->unserialize($cache->value);
    }

    /**
     * Store an item in the cache for a given number of seconds.
	 * 存储项目至缓存中使用给定秒数
     *
     * @param  string  $key
     * @param  mixed  $value
     * @param  int  $seconds
     * @return bool
     */
    public function put($key, $value, $seconds)
    {
        $key = $this->prefix.$key;

        $value = $this->serialize($value);

        $expiration = $this->getTime() + $seconds;

        try {
            return $this->table()->insert(compact('key', 'value', 'expiration'));
        } catch (Exception $e) {
            $result = $this->table()->where('key', $key)->update(compact('value', 'expiration'));

            return $result > 0;
        }
    }

    /**
     * Increment the value of an item in the cache.
	 * 增加缓存中项目的值
     *
     * @param  string  $key
     * @param  mixed  $value
     * @return int|bool
     */
    public function increment($key, $value = 1)
    {
        return $this->incrementOrDecrement($key, $value, function ($current, $value) {
            return $current + $value;
        });
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
        return $this->incrementOrDecrement($key, $value, function ($current, $value) {
            return $current - $value;
        });
    }

    /**
     * Increment or decrement an item in the cache.
	 * 增加或减少缓存中的项目
     *
     * @param  string  $key
     * @param  mixed  $value
     * @param  \Closure  $callback
     * @return int|bool
     */
    protected function incrementOrDecrement($key, $value, Closure $callback)
    {
        return $this->connection->transaction(function () use ($key, $value, $callback) {
            $prefixed = $this->prefix.$key;

            $cache = $this->table()->where('key', $prefixed)
                        ->lockForUpdate()->first();

            // If there is no value in the cache, we will return false here. Otherwise the
            // value will be decrypted and we will proceed with this function to either
            // increment or decrement this value based on the given action callbacks.
			// 如果缓存中没有值，我们将在这里返回false。
			// 否则值将被加密，我们将曳光弹执行此函数并根据给定的动作回调递增或递减此值。
            if (is_null($cache)) {
                return false;
            }

            $cache = is_array($cache) ? (object) $cache : $cache;

            $current = $this->unserialize($cache->value);

            // Here we'll call this callback function that was given to the function which
            // is used to either increment or decrement the function. We use a callback
            // so we do not have to recreate all this logic in each of the functions.
			// 我们将调用这个回调函数，它被赱用于递增或递减函数。
			// 我们使用回调，以便不必在每个函数中重新创建所有这些逻辑。
            $new = $callback((int) $current, $value);

            if (! is_numeric($current)) {
                return false;
            }

            // Here we will update the values in the table. We will also encrypt the value
            // since database cache values are encrypted by default with secure storage
            // that can't be easily read. We will return the new value after storing.
			// 我们将更新表中的值。我们还将对值进行加密，
			// 因为数据库缓存值默认使用安全存储进行加密以致不容易懂。
			// 我们将在存储后返回新值。
            $this->table()->where('key', $prefixed)->update([
                'value' => $this->serialize($new),
            ]);

            return $new;
        });
    }

    /**
     * Get the current system time.
	 * 得到当前系统时间
     *
     * @return int
     */
    protected function getTime()
    {
        return $this->currentTime();
    }

    /**
     * Store an item in the cache indefinitely.
	 * 存储项目在无限期缓存中
     *
     * @param  string  $key
     * @param  mixed  $value
     * @return bool
     */
    public function forever($key, $value)
    {
        return $this->put($key, $value, 315360000);
    }

    /**
     * Remove an item from the cache.
	 * 移除一个项目从缓存中
     *
     * @param  string  $key
     * @return bool
     */
    public function forget($key)
    {
        $this->table()->where('key', '=', $this->prefix.$key)->delete();

        return true;
    }

    /**
     * Remove all items from the cache.
	 * 移除所有项从缓存中
     *
     * @return bool
     */
    public function flush()
    {
        $this->table()->delete();

        return true;
    }

    /**
     * Get a query builder for the cache table.
	 * 得到缓存表的查询生成器
     *
     * @return \Illuminate\Database\Query\Builder
     */
    protected function table()
    {
        return $this->connection->table($this->table);
    }

    /**
     * Get the underlying database connection.
	 * 得到底层数据库连接
     *
     * @return \Illuminate\Database\ConnectionInterface
     */
    public function getConnection()
    {
        return $this->connection;
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
     * Serialize the given value.
	 * 序列化给定值
     *
     * @param  mixed  $value
     * @return string
     */
    protected function serialize($value)
    {
        $result = serialize($value);

        if ($this->connection instanceof PostgresConnection && Str::contains($result, "\0")) {
            $result = base64_encode($result);
        }

        return $result;
    }

    /**
     * Unserialize the given value.
	 * 反序列化给定值
     *
     * @param  string  $value
     * @return mixed
     */
    protected function unserialize($value)
    {
        if ($this->connection instanceof PostgresConnection && ! Str::contains($value, [':', ';'])) {
            $value = base64_decode($value);
        }

        return unserialize($value);
    }
}

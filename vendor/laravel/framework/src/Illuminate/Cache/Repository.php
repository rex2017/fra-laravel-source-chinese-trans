<?php
/**
 * 缓存资源库
 */

namespace Illuminate\Cache;

use ArrayAccess;
use BadMethodCallException;
use Closure;
use DateTimeInterface;
use Illuminate\Cache\Events\CacheHit;
use Illuminate\Cache\Events\CacheMissed;
use Illuminate\Cache\Events\KeyForgotten;
use Illuminate\Cache\Events\KeyWritten;
use Illuminate\Contracts\Cache\Repository as CacheContract;
use Illuminate\Contracts\Cache\Store;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Support\Carbon;
use Illuminate\Support\InteractsWithTime;
use Illuminate\Support\Traits\Macroable;

/**
 * @mixin \Illuminate\Contracts\Cache\Store
 */
class Repository implements ArrayAccess, CacheContract
{
    use InteractsWithTime;
    use Macroable {
        __call as macroCall;
    }

    /**
     * The cache store implementation.
	 * 缓存存储实现
     *
     * @var \Illuminate\Contracts\Cache\Store
     */
    protected $store;

    /**
     * The event dispatcher implementation.
	 * 事件分派器实现
     *
     * @var \Illuminate\Contracts\Events\Dispatcher
     */
    protected $events;

    /**
     * The default number of seconds to store items.
	 * 默认存储时间，3600秒
     *
     * @var int|null
     */
    protected $default = 3600;

    /**
     * Create a new cache repository instance.
	 * 创建新的缓存资源实例
     *
     * @param  \Illuminate\Contracts\Cache\Store  $store
     * @return void
     */
    public function __construct(Store $store)
    {
        $this->store = $store;
    }

    /**
     * Determine if an item exists in the cache.
	 * 确定缓存中是否存在此项
     *
     * @param  string  $key
     * @return bool
     */
    public function has($key)
    {
        return ! is_null($this->get($key));
    }

    /**
     * Determine if an item doesn't exist in the cache.
	 * 确定某个项目是否在缓存中不存在
     *
     * @param  string  $key
     * @return bool
     */
    public function missing($key)
    {
        return ! $this->has($key);
    }

    /**
     * Retrieve an item from the cache by key.
	 * 检索键从缓存中
     *
     * @param  string  $key
     * @param  mixed  $default
     * @return mixed
     */
    public function get($key, $default = null)
    {
        if (is_array($key)) {
            return $this->many($key);
        }

        $value = $this->store->get($this->itemKey($key));

        // If we could not find the cache value, we will fire the missed event and get
        // the default value for this cache value. This default could be a callback
        // so we will execute the value function which will resolve it if needed.
		// 如果我们找不到缓存值，我们将触发错过的事件并获取此缓存的默认值。
		// 这个默认值可能是回调，因此我们将执行value函数，该函数将在需要时解析它。
        if (is_null($value)) {
            $this->event(new CacheMissed($key));

            $value = value($default);
        } else {
            $this->event(new CacheHit($key, $value));
        }

        return $value;
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
        $values = $this->store->many(collect($keys)->map(function ($value, $key) {
            return is_string($key) ? $key : $value;
        })->values()->all());

        return collect($values)->map(function ($value, $key) use ($keys) {
            return $this->handleManyResult($keys, $key, $value);
        })->all();
    }

    /**
     * {@inheritdoc}
     */
    public function getMultiple($keys, $default = null)
    {
        $defaults = [];

        foreach ($keys as $key) {
            $defaults[$key] = $default;
        }

        return $this->many($defaults);
    }

    /**
     * Handle a result for the "many" method.
	 * 处理"many"方法的结果
     *
     * @param  array  $keys
     * @param  string  $key
     * @param  mixed  $value
     * @return mixed
     */
    protected function handleManyResult($keys, $key, $value)
    {
        // If we could not find the cache value, we will fire the missed event and get
        // the default value for this cache value. This default could be a callback
        // so we will execute the value function which will resolve it if needed.
		// 如果我们找不到缓存值，我们将触发错过的事件并获取此缓存值的默认值。
		// 此默认值可能是回调。因此我们将执行value函数，该函数将在需要时解析它。
        if (is_null($value)) {
            $this->event(new CacheMissed($key));

            return isset($keys[$key]) ? value($keys[$key]) : null;
        }

        // If we found a valid value we will fire the "hit" event and return the value
        // back from this function. The "hit" event gives developers an opportunity
        // to listen for every possible cache "hit" throughout this applications.
		// 如果我们找到一个有效值，我们将触发"hit"事件并返回该值从这个函数。
		// "hit"事件给了开发者一个机会去监听每一个可能的缓存"hit"。
        $this->event(new CacheHit($key, $value));

        return $value;
    }

    /**
     * Retrieve an item from the cache and delete it.
	 * 检索项目从缓存中并删除它
     *
     * @param  string  $key
     * @param  mixed  $default
     * @return mixed
     */
    public function pull($key, $default = null)
    {
        return tap($this->get($key, $default), function () use ($key) {
            $this->forget($key);
        });
    }

    /**
     * Store an item in the cache.
	 * 存储项目至缓存中
     *
     * @param  string  $key
     * @param  mixed  $value
     * @param  \DateTimeInterface|\DateInterval|int|null  $ttl
     * @return bool
     */
    public function put($key, $value, $ttl = null)
    {
        if (is_array($key)) {
            return $this->putMany($key, $value);
        }

        if ($ttl === null) {
            return $this->forever($key, $value);
        }

        $seconds = $this->getSeconds($ttl);

        if ($seconds <= 0) {
            return $this->forget($key);
        }

        $result = $this->store->put($this->itemKey($key), $value, $seconds);

        if ($result) {
            $this->event(new KeyWritten($key, $value, $seconds));
        }

        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public function set($key, $value, $ttl = null)
    {
        return $this->put($key, $value, $ttl);
    }

    /**
     * Store multiple items in the cache for a given number of seconds.
	 * 存储多少项目至缓存中使用给定秒数
     *
     * @param  array  $values
     * @param  \DateTimeInterface|\DateInterval|int|null  $ttl
     * @return bool
     */
    public function putMany(array $values, $ttl = null)
    {
        if ($ttl === null) {
            return $this->putManyForever($values);
        }

        $seconds = $this->getSeconds($ttl);

        if ($seconds <= 0) {
            return $this->deleteMultiple(array_keys($values));
        }

        $result = $this->store->putMany($values, $seconds);

        if ($result) {
            foreach ($values as $key => $value) {
                $this->event(new KeyWritten($key, $value, $seconds));
            }
        }

        return $result;
    }

    /**
     * Store multiple items in the cache indefinitely.
	 * 存储多个项目至缓存中无限期
     *
     * @param  array  $values
     * @return bool
     */
    protected function putManyForever(array $values)
    {
        $result = true;

        foreach ($values as $key => $value) {
            if (! $this->forever($key, $value)) {
                $result = false;
            }
        }

        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public function setMultiple($values, $ttl = null)
    {
        return $this->putMany(is_array($values) ? $values : iterator_to_array($values), $ttl);
    }

    /**
     * Store an item in the cache if the key does not exist.
	 * 存储项目至缓存中如果键不存在
     *
     * @param  string  $key
     * @param  mixed  $value
     * @param  \DateTimeInterface|\DateInterval|int|null  $ttl
     * @return bool
     */
    public function add($key, $value, $ttl = null)
    {
        if ($ttl !== null) {
            if ($this->getSeconds($ttl) <= 0) {
                return false;
            }

            // If the store has an "add" method we will call the method on the store so it
            // has a chance to override this logic. Some drivers better support the way
            // this operation should work with a total "atomic" implementation of it.
			// 如果存储有一个"add"方法，我们将调用存储上的方法以便有机会推翻这一逻辑。
			// 一些驱动更好支持这个操作方式，操作应该与它的完全"原子"实现一起工作。
            if (method_exists($this->store, 'add')) {
                $seconds = $this->getSeconds($ttl);

                return $this->store->add(
                    $this->itemKey($key), $value, $seconds
                );
            }
        }

        // If the value did not exist in the cache, we will put the value in the cache
        // so it exists for subsequent requests. Then, we will return true so it is
        // easy to know if the value gets added. Otherwise, we will return false.
		// 如果缓存中不存在该值，我们将把该值放入缓存中。
		// 因此，它存在于后续请求中。然后，我们将按原样返回true，
		// 很容易知道是否增加了价值。否则，我们将返回false。
        if (is_null($this->get($key))) {
            return $this->put($key, $value, $ttl);
        }

        return false;
    }

    /**
     * Increment the value of an item in the cache.
	 * 增加缓存中项目值
     *
     * @param  string  $key
     * @param  mixed  $value
     * @return int|bool
     */
    public function increment($key, $value = 1)
    {
        return $this->store->increment($key, $value);
    }

    /**
     * Decrement the value of an item in the cache.
	 * 递减缓存中项目值
     *
     * @param  string  $key
     * @param  mixed  $value
     * @return int|bool
     */
    public function decrement($key, $value = 1)
    {
        return $this->store->decrement($key, $value);
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
        $result = $this->store->forever($this->itemKey($key), $value);

        if ($result) {
            $this->event(new KeyWritten($key, $value));
        }

        return $result;
    }

    /**
     * Get an item from the cache, or execute the given Closure and store the result.
	 * 得到一个项目从缓存中，或者执行给定的Closure并存储结果。
     *
     * @param  string  $key
     * @param  \DateTimeInterface|\DateInterval|int|null  $ttl
     * @param  \Closure  $callback
     * @return mixed
     */
    public function remember($key, $ttl, Closure $callback)
    {
        $value = $this->get($key);

        // If the item exists in the cache we will just return this immediately and if
        // not we will execute the given Closure and cache the result of that for a
        // given number of seconds so it's available for all subsequent requests.
		// 如果缓存中存在该项，我们将立即返回该项，
		// 如果我们不执行给定闭包并缓存结果给定秒数，因此它可用于所有后续请求。
        if (! is_null($value)) {
            return $value;
        }

        $this->put($key, $value = $callback(), $ttl);

        return $value;
    }

    /**
     * Get an item from the cache, or execute the given Closure and store the result forever.
	 * 得到一个项目从缓存中，或者执行给定的闭包并永久存储结果。
     *
     * @param  string  $key
     * @param  \Closure  $callback
     * @return mixed
     */
    public function sear($key, Closure $callback)
    {
        return $this->rememberForever($key, $callback);
    }

    /**
     * Get an item from the cache, or execute the given Closure and store the result forever.
	 * 得到一个项目从缓存中，或者执行给定的闭包并永久存储结果。
     *
     * @param  string  $key
     * @param  \Closure  $callback
     * @return mixed
     */
    public function rememberForever($key, Closure $callback)
    {
        $value = $this->get($key);

        // If the item exists in the cache we will just return this immediately
        // and if not we will execute the given Closure and cache the result
        // of that forever so it is available for all subsequent requests.
		// 如果缓存中存在该项，我们将立即返回该项，
		// 如果我们不执行给定闭包并缓存结果给定秒数，因此它可用于所有后续请求。
        if (! is_null($value)) {
            return $value;
        }

        $this->forever($key, $value = $callback());

        return $value;
    }

    /**
     * Remove an item from the cache.
	 * 移除缓存中的项目
     *
     * @param  string  $key
     * @return bool
     */
    public function forget($key)
    {
        return tap($this->store->forget($this->itemKey($key)), function ($result) use ($key) {
            if ($result) {
                $this->event(new KeyForgotten($key));
            }
        });
    }

    /**
     * {@inheritdoc}
     */
    public function delete($key)
    {
        return $this->forget($key);
    }

    /**
     * {@inheritdoc}
     */
    public function deleteMultiple($keys)
    {
        $result = true;

        foreach ($keys as $key) {
            if (! $this->forget($key)) {
                $result = false;
            }
        }

        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public function clear()
    {
        return $this->store->flush();
    }

    /**
     * Begin executing a new tags operation if the store supports it.
	 * 开始执行新的标记操作，如果存储支持
     *
     * @param  array|mixed  $names
     * @return \Illuminate\Cache\TaggedCache
     *
     * @throws \BadMethodCallException
     */
    public function tags($names)
    {
        if (! method_exists($this->store, 'tags')) {
            throw new BadMethodCallException('This cache store does not support tagging.');
        }

        $cache = $this->store->tags(is_array($names) ? $names : func_get_args());

        if (! is_null($this->events)) {
            $cache->setEventDispatcher($this->events);
        }

        return $cache->setDefaultCacheTime($this->default);
    }

    /**
     * Format the key for a cache item.
	 * 格式化缓存项目的键值
     *
     * @param  string  $key
     * @return string
     */
    protected function itemKey($key)
    {
        return $key;
    }

    /**
     * Get the default cache time.
	 * 得到默认缓存时间
     *
     * @return int|null
     */
    public function getDefaultCacheTime()
    {
        return $this->default;
    }

    /**
     * Set the default cache time in seconds.
	 * 设置默认缓存时间
     *
     * @param  int|null  $seconds
     * @return $this
     */
    public function setDefaultCacheTime($seconds)
    {
        $this->default = $seconds;

        return $this;
    }

    /**
     * Get the cache store implementation.
	 * 得到缓存存储实现
     *
     * @return \Illuminate\Contracts\Cache\Store
     */
    public function getStore()
    {
        return $this->store;
    }

    /**
     * Fire an event for this cache instance.
	 * 触发此缓存实例的事件
     *
     * @param  string  $event
     * @return void
     */
    protected function event($event)
    {
        if (isset($this->events)) {
            $this->events->dispatch($event);
        }
    }

    /**
     * Get the event dispatcher instance.
	 * 得到事件调度实例
     *
     * @return \Illuminate\Contracts\Events\Dispatcher
     */
    public function getEventDispatcher()
    {
        return $this->events;
    }

    /**
     * Set the event dispatcher instance.
	 * 设置事件调度实例
     *
     * @param  \Illuminate\Contracts\Events\Dispatcher  $events
     * @return void
     */
    public function setEventDispatcher(Dispatcher $events)
    {
        $this->events = $events;
    }

    /**
     * Determine if a cached value exists.
	 * 确定缓存值是否存在
     *
     * @param  string  $key
     * @return bool
     */
    public function offsetExists($key)
    {
        return $this->has($key);
    }

    /**
     * Retrieve an item from the cache by key.
	 * 检索项按键从缓存中
     *
     * @param  string  $key
     * @return mixed
     */
    public function offsetGet($key)
    {
        return $this->get($key);
    }

    /**
     * Store an item in the cache for the default time.
	 * 存储项在缓存中在默认时间
     *
     * @param  string  $key
     * @param  mixed  $value
     * @return void
     */
    public function offsetSet($key, $value)
    {
        $this->put($key, $value, $this->default);
    }

    /**
     * Remove an item from the cache.
	 * 移除一项从缓存中
     *
     * @param  string  $key
     * @return void
     */
    public function offsetUnset($key)
    {
        $this->forget($key);
    }

    /**
     * Calculate the number of seconds for the given TTL.
	 * 计算TTl秒数
     *
     * @param  \DateTimeInterface|\DateInterval|int  $ttl
     * @return int
     */
    protected function getSeconds($ttl)
    {
        $duration = $this->parseDateInterval($ttl);

        if ($duration instanceof DateTimeInterface) {
            $duration = Carbon::now()->diffInRealSeconds($duration, false);
        }

        return (int) $duration > 0 ? $duration : 0;
    }

    /**
     * Handle dynamic calls into macros or pass missing methods to the store.
	 * 处理动态调用
     *
     * @param  string  $method
     * @param  array  $parameters
     * @return mixed
     */
    public function __call($method, $parameters)
    {
        if (static::hasMacro($method)) {
            return $this->macroCall($method, $parameters);
        }

        return $this->store->$method(...$parameters);
    }

    /**
     * Clone cache repository instance.
	 * 克隆缓存资源实例
     *
     * @return void
     */
    public function __clone()
    {
        $this->store = clone $this->store;
    }
}

<?php
/**
 * 控制台，缓存事件互斥锁
 */

namespace Illuminate\Console\Scheduling;

use Illuminate\Contracts\Cache\Factory as Cache;

class CacheEventMutex implements EventMutex
{
    /**
     * The cache repository implementation.
	 * 缓存资源库实现
     *
     * @var \Illuminate\Contracts\Cache\Factory
     */
    public $cache;

    /**
     * The cache store that should be used.
	 * 应该使用的缓存存储
     *
     * @var string|null
     */
    public $store;

    /**
     * Create a new overlapping strategy.
	 * 创建新的重叠策略
     *
     * @param  \Illuminate\Contracts\Cache\Factory  $cache
     * @return void
     */
    public function __construct(Cache $cache)
    {
        $this->cache = $cache;
    }

    /**
     * Attempt to obtain an event mutex for the given event.
	 * 尝试获取给定事件的事件互斥锁
     *
     * @param  \Illuminate\Console\Scheduling\Event  $event
     * @return bool
     */
    public function create(Event $event)
    {
        return $this->cache->store($this->store)->add(
            $event->mutexName(), true, $event->expiresAt * 60
        );
    }

    /**
     * Determine if an event mutex exists for the given event.
	 * 确定给定事件是否存在事件互斥锁
     *
     * @param  \Illuminate\Console\Scheduling\Event  $event
     * @return bool
     */
    public function exists(Event $event)
    {
        return $this->cache->store($this->store)->has($event->mutexName());
    }

    /**
     * Clear the event mutex for the given event.
	 * 清除给定事件的事件互斥锁
     *
     * @param  \Illuminate\Console\Scheduling\Event  $event
     * @return void
     */
    public function forget(Event $event)
    {
        $this->cache->store($this->store)->forget($event->mutexName());
    }

    /**
     * Specify the cache store that should be used.
	 * 指定应该使用的缓存存储
     *
     * @param  string  $store
     * @return $this
     */
    public function useStore($store)
    {
        $this->store = $store;

        return $this;
    }
}

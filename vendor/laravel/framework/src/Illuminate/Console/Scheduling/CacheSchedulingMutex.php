<?php
/**
 * 控制台，缓存调度互斥锁
 */

namespace Illuminate\Console\Scheduling;

use DateTimeInterface;
use Illuminate\Contracts\Cache\Factory as Cache;

class CacheSchedulingMutex implements SchedulingMutex
{
    /**
     * The cache factory implementation.
	 * 缓存工厂实现
     *
     * @var \Illuminate\Contracts\Cache\Factory
     */
    public $cache;

    /**
     * The cache store that should be used.
	 * 缓存存储应该被使用的
     *
     * @var string|null
     */
    public $store;

    /**
     * Create a new scheduling strategy.
	 * 创建新的调度策略
     *
     * @param  \Illuminate\Contracts\Cache\Factory  $cache
     * @return void
     */
    public function __construct(Cache $cache)
    {
        $this->cache = $cache;
    }

    /**
     * Attempt to obtain a scheduling mutex for the given event.
	 * 清除给定事件的调度互斥锁
     *
     * @param  \Illuminate\Console\Scheduling\Event  $event
     * @param  \DateTimeInterface  $time
     * @return bool
     */
    public function create(Event $event, DateTimeInterface $time)
    {
        return $this->cache->store($this->store)->add(
            $event->mutexName().$time->format('Hi'), true, 3600
        );
    }

    /**
     * Determine if a scheduling mutex exists for the given event.
	 * 确定是否存在给定事件的调度互斥锁
     *
     * @param  \Illuminate\Console\Scheduling\Event  $event
     * @param  \DateTimeInterface  $time
     * @return bool
     */
    public function exists(Event $event, DateTimeInterface $time)
    {
        return $this->cache->store($this->store)->has(
            $event->mutexName().$time->format('Hi')
        );
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

<?php
/**
 * 缓存数据锁定
 */

namespace Illuminate\Cache;

use Carbon\Carbon;

class ArrayLock extends Lock
{
    /**
     * The parent array cache store.
	 * 缓存存储
     *
     * @var \Illuminate\Cache\ArrayStore
     */
    protected $store;

    /**
     * Create a new lock instance.
	 * 创建新的锁定实例
     *
     * @param  \Illuminate\Cache\ArrayStore  $store
     * @param  string  $name
     * @param  int  $seconds
     * @param  string|null  $owner
     * @return void
     */
    public function __construct($store, $name, $seconds, $owner = null)
    {
        parent::__construct($name, $seconds, $owner);

        $this->store = $store;
    }

    /**
     * Attempt to acquire the lock.
	 * 尝试获取锁
     *
     * @return bool
     */
    public function acquire()
    {
        $expiration = $this->store->locks[$this->name]['expiresAt'] ?? Carbon::now()->addSecond();

        if ($this->exists() && $expiration->isFuture()) {
            return false;
        }

        $this->store->locks[$this->name] = [
            'owner' => $this->owner,
            'expiresAt' => $this->seconds === 0 ? null : Carbon::now()->addSeconds($this->seconds),
        ];

        return true;
    }

    /**
     * Determine if the current lock exists.
	 * 当前锁是否存在
     *
     * @return bool
     */
    protected function exists()
    {
        return isset($this->store->locks[$this->name]);
    }

    /**
     * Release the lock.
	 * 释放锁
     *
     * @return bool
     */
    public function release()
    {
        if (! $this->exists()) {
            return false;
        }

        if (! $this->isOwnedByCurrentProcess()) {
            return false;
        }

        $this->forceRelease();

        return true;
    }

    /**
     * Returns the owner value written into the driver for this lock.
	 * 返回写入此锁的驱动程序的所有者值
     *
     * @return string
     */
    protected function getCurrentOwner()
    {
        return $this->store->locks[$this->name]['owner'];
    }

    /**
     * Releases this lock in disregard of ownership.
	 * 释放锁，而不考虑所有权
     *
     * @return void
     */
    public function forceRelease()
    {
        unset($this->store->locks[$this->name]);
    }
}

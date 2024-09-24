<?php
/**
 * 缓存Memcached锁
 */

namespace Illuminate\Cache;

class MemcachedLock extends Lock
{
    /**
     * The Memcached instance.
	 * Memcached实例
     *
     * @var \Memcached
     */
    protected $memcached;

    /**
     * Create a new lock instance.
	 * 创建新的锁实例
     *
     * @param  \Memcached  $memcached
     * @param  string  $name
     * @param  int  $seconds
     * @param  string|null  $owner
     * @return void
     */
    public function __construct($memcached, $name, $seconds, $owner = null)
    {
        parent::__construct($name, $seconds, $owner);

        $this->memcached = $memcached;
    }

    /**
     * Attempt to acquire the lock.
	 * 尝试获取锁
     *
     * @return bool
     */
    public function acquire()
    {
        return $this->memcached->add(
            $this->name, $this->owner, $this->seconds
        );
    }

    /**
     * Release the lock.
	 * 释放锁
     *
     * @return bool
     */
    public function release()
    {
        if ($this->isOwnedByCurrentProcess()) {
            return $this->memcached->delete($this->name);
        }

        return false;
    }

    /**
     * Releases this lock in disregard of ownership.
	 * 释放锁不考虑所有权
     *
     * @return void
     */
    public function forceRelease()
    {
        $this->memcached->delete($this->name);
    }

    /**
     * Returns the owner value written into the driver for this lock.
	 * 返回写入此锁的驱动程序的所有者
     *
     * @return mixed
     */
    protected function getCurrentOwner()
    {
        return $this->memcached->get($this->name);
    }
}

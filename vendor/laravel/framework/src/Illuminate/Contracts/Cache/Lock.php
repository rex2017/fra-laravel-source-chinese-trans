<?php
/**
 * 契约，缓存锁接口
 */

namespace Illuminate\Contracts\Cache;

interface Lock
{
    /**
     * Attempt to acquire the lock.
	 * 尝试获取锁
     *
     * @param  callable|null  $callback
     * @return mixed
     */
    public function get($callback = null);

    /**
     * Attempt to acquire the lock for the given number of seconds.
	 * 尝试在给定的秒数内获取锁
     *
     * @param  int  $seconds
     * @param  callable|null  $callback
     * @return mixed
     */
    public function block($seconds, $callback = null);

    /**
     * Release the lock.
	 * 释放锁
     *
     * @return bool
     */
    public function release();

    /**
     * Returns the current owner of the lock.
	 * 返回锁所有者
     *
     * @return string
     */
    public function owner();

    /**
     * Releases this lock in disregard of ownership.
	 * 释放锁而不考虑所有权
     *
     * @return void
     */
    public function forceRelease();
}

<?php
/**
 * 契约，锁提供者接口
 */

namespace Illuminate\Contracts\Cache;

interface LockProvider
{
    /**
     * Get a lock instance.
	 * 得到一个锁实例
     *
     * @param  string  $name
     * @param  int  $seconds
     * @param  string|null  $owner
     * @return \Illuminate\Contracts\Cache\Lock
     */
    public function lock($name, $seconds = 0, $owner = null);

    /**
     * Restore a lock instance using the owner identifier.
	 * 重置锁实例
     *
     * @param  string  $name
     * @param  string  $owner
     * @return \Illuminate\Contracts\Cache\Lock
     */
    public function restoreLock($name, $owner);
}

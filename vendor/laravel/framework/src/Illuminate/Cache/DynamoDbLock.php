<?php
/**
 * 缓存DynamoDB锁
 */

namespace Illuminate\Cache;

class DynamoDbLock extends Lock
{
    /**
     * The DynamoDB client instance.
	 * DynamoDB客户端实例
     *
     * @var \Illuminate\Cache\DynamoDbStore
     */
    protected $dynamo;

    /**
     * Create a new lock instance.
	 * 创建新的锁实例
     *
     * @param  \Illuminate\Cache\DynamoDbStore  $dynamo
     * @param  string  $name
     * @param  int  $seconds
     * @param  string|null  $owner
     * @return void
     */
    public function __construct(DynamoDbStore $dynamo, $name, $seconds, $owner = null)
    {
        parent::__construct($name, $seconds, $owner);

        $this->dynamo = $dynamo;
    }

    /**
     * Attempt to acquire the lock.
	 * 尝试获取锁
     *
     * @return bool
     */
    public function acquire()
    {
        return $this->dynamo->add(
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
            return $this->dynamo->forget($this->name);
        }

        return false;
    }

    /**
     * Release this lock in disregard of ownership.
	 * 释放锁，不顾所有权
     *
     * @return void
     */
    public function forceRelease()
    {
        $this->dynamo->forget($this->name);
    }

    /**
     * Returns the owner value written into the driver for this lock.
	 * 返回写入此锁的驱动程序的所有者值
     *
     * @return mixed
     */
    protected function getCurrentOwner()
    {
        return $this->dynamo->get($this->name);
    }
}

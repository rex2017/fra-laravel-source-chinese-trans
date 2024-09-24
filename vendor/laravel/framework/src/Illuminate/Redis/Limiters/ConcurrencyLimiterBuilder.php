<?php
/**
 * Redis，并发限制生成器
 */

namespace Illuminate\Redis\Limiters;

use Illuminate\Contracts\Redis\LimiterTimeoutException;
use Illuminate\Support\InteractsWithTime;

class ConcurrencyLimiterBuilder
{
    use InteractsWithTime;

    /**
     * The Redis connection.
	 * Redis连接
     *
     * @var \Illuminate\Redis\Connections\Connection
     */
    public $connection;

    /**
     * The name of the lock.
	 * 锁名称
     *
     * @var string
     */
    public $name;

    /**
     * The maximum number of entities that can hold the lock at the same time.
	 * 可以同时持有该锁的最大实体数
     *
     * @var int
     */
    public $maxLocks;

    /**
     * The number of seconds to maintain the lock until it is automatically released.
	 * 在自动释放锁之前保持锁的秒数
     *
     * @var int
     */
    public $releaseAfter = 60;

    /**
     * The amount of time to block until a lock is available.
	 * 在锁定可用之前阻塞的时间
     *
     * @var int
     */
    public $timeout = 3;

    /**
     * Create a new builder instance.
	 * 创建新的构建器实例
     *
     * @param  \Illuminate\Redis\Connections\Connection  $connection
     * @param  string  $name
     * @return void
     */
    public function __construct($connection, $name)
    {
        $this->name = $name;
        $this->connection = $connection;
    }

    /**
     * Set the maximum number of locks that can obtained per time window.
	 * 设置每个时间窗口可以获得的最大锁数
     *
     * @param  int  $maxLocks
     * @return $this
     */
    public function limit($maxLocks)
    {
        $this->maxLocks = $maxLocks;

        return $this;
    }

    /**
     * Set the number of seconds until the lock will be released.
	 * 设置锁释放前的秒数
     *
     * @param  int  $releaseAfter
     * @return $this
     */
    public function releaseAfter($releaseAfter)
    {
        $this->releaseAfter = $this->secondsUntil($releaseAfter);

        return $this;
    }

    /**
     * Set the amount of time to block until a lock is available.
	 * 设置锁定可用之前的阻塞时间
     *
     * @param  int  $timeout
     * @return $this
     */
    public function block($timeout)
    {
        $this->timeout = $timeout;

        return $this;
    }

    /**
     * Execute the given callback if a lock is obtained, otherwise call the failure callback.
	 * 执行给定的回调，如果获得了锁，否则调用失败回调。
     *
     * @param  callable  $callback
     * @param  callable|null  $failure
     * @return mixed
     *
     * @throws \Illuminate\Contracts\Redis\LimiterTimeoutException
     */
    public function then(callable $callback, callable $failure = null)
    {
        try {
            return (new ConcurrencyLimiter(
                $this->connection, $this->name, $this->maxLocks, $this->releaseAfter
            ))->block($this->timeout, $callback);
        } catch (LimiterTimeoutException $e) {
            if ($failure) {
                return $failure($e);
            }

            throw $e;
        }
    }
}

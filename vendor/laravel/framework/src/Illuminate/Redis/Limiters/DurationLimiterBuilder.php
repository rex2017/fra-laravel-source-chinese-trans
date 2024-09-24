<?php
/**
 * Redis，持续时间限制生成器
 */

namespace Illuminate\Redis\Limiters;

use Illuminate\Contracts\Redis\LimiterTimeoutException;
use Illuminate\Support\InteractsWithTime;

class DurationLimiterBuilder
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
     * The maximum number of locks that can obtained per time window.
	 * 每个时间窗口可以获得的最大锁数
     *
     * @var int
     */
    public $maxLocks;

    /**
     * The amount of time the lock window is maintained.
	 * 锁窗口被维护的时间
     *
     * @var int
     */
    public $decay;

    /**
     * The amount of time to block until a lock is available.
	 * 阻塞的时间在锁定可用之前
     *
     * @var int
     */
    public $timeout = 3;

    /**
     * Create a new builder instance.
	 * 创建新的生成器实例
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
    public function allow($maxLocks)
    {
        $this->maxLocks = $maxLocks;

        return $this;
    }

    /**
     * Set the amount of time the lock window is maintained.
	 * 设置锁窗口的维护时间
     *
     * @param  int  $decay
     * @return $this
     */
    public function every($decay)
    {
        $this->decay = $this->secondsUntil($decay);

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
	 * 执行给定的回调如果获得了锁，否则调用失败回调。
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
            return (new DurationLimiter(
                $this->connection, $this->name, $this->maxLocks, $this->decay
            ))->block($this->timeout, $callback);
        } catch (LimiterTimeoutException $e) {
            if ($failure) {
                return $failure($e);
            }

            throw $e;
        }
    }
}

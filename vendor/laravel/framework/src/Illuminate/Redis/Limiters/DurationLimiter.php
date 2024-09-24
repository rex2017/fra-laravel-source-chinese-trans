<?php
/**
 * Redis，负载限制器
 */

namespace Illuminate\Redis\Limiters;

use Illuminate\Contracts\Redis\LimiterTimeoutException;

class DurationLimiter
{
    /**
     * The Redis factory implementation.
	 * Redis工厂实例
     *
     * @var \Illuminate\Redis\Connections\Connection
     */
    private $redis;

    /**
     * The unique name of the lock.
	 * 锁的唯一名称
     *
     * @var string
     */
    private $name;

    /**
     * The allowed number of concurrent tasks.
	 * 允许的并发任务数
     *
     * @var int
     */
    private $maxLocks;

    /**
     * The number of seconds a slot should be maintained.
	 * 应该维护一个槽位的秒数
     *
     * @var int
     */
    private $decay;

    /**
     * The timestamp of the end of the current duration.
	 * 当前持续时间结束的时间戳
     *
     * @var int
     */
    public $decaysAt;

    /**
     * The number of remaining slots.
	 * 剩余槽位的数量
     *
     * @var int
     */
    public $remaining;

    /**
     * Create a new duration limiter instance.
	 * 创建新的持续时间限制器实例
     *
     * @param  \Illuminate\Redis\Connections\Connection  $redis
     * @param  string  $name
     * @param  int  $maxLocks
     * @param  int  $decay
     * @return void
     */
    public function __construct($redis, $name, $maxLocks, $decay)
    {
        $this->name = $name;
        $this->decay = $decay;
        $this->redis = $redis;
        $this->maxLocks = $maxLocks;
    }

    /**
     * Attempt to acquire the lock for the given number of seconds.
	 * 尝试获取锁在给定的秒数内
     *
     * @param  int  $timeout
     * @param  callable|null  $callback
     * @return mixed
     *
     * @throws \Illuminate\Contracts\Redis\LimiterTimeoutException
     */
    public function block($timeout, $callback = null)
    {
        $starting = time();

        while (! $this->acquire()) {
            if (time() - $timeout >= $starting) {
                throw new LimiterTimeoutException;
            }

            usleep(750 * 1000);
        }

        if (is_callable($callback)) {
            return $callback();
        }

        return true;
    }

    /**
     * Attempt to acquire the lock.
	 * 尝试获取锁
     *
     * @return bool
     */
    public function acquire()
    {
        $results = $this->redis->eval(
            $this->luaScript(), 1, $this->name, microtime(true), time(), $this->decay, $this->maxLocks
        );

        $this->decaysAt = $results[1];

        $this->remaining = max(0, $results[2]);

        return (bool) $results[0];
    }

    /**
     * Get the Lua script for acquiring a lock.
	 * 得到用于获取锁的Lua脚本
     *
     * KEYS[1] - The limiter name
     * ARGV[1] - Current time in microseconds
     * ARGV[2] - Current time in seconds
     * ARGV[3] - Duration of the bucket
     * ARGV[4] - Allowed number of tasks
     *
     * @return string
     */
    protected function luaScript()
    {
        return <<<'LUA'
local function reset()
    redis.call('HMSET', KEYS[1], 'start', ARGV[2], 'end', ARGV[2] + ARGV[3], 'count', 1)
    return redis.call('EXPIRE', KEYS[1], ARGV[3] * 2)
end

if redis.call('EXISTS', KEYS[1]) == 0 then
    return {reset(), ARGV[2] + ARGV[3], ARGV[4] - 1}
end

if ARGV[1] >= redis.call('HGET', KEYS[1], 'start') and ARGV[1] <= redis.call('HGET', KEYS[1], 'end') then
    return {
        tonumber(redis.call('HINCRBY', KEYS[1], 'count', 1)) <= tonumber(ARGV[4]),
        redis.call('HGET', KEYS[1], 'end'),
        ARGV[4] - redis.call('HGET', KEYS[1], 'count')
    }
end

return {reset(), ARGV[2] + ARGV[3], ARGV[4] - 1}
LUA;
    }
}

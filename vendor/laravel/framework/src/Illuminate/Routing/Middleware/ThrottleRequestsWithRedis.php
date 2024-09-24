<?php
/**
 * 路由，中间件节流请求与Redis
 */

namespace Illuminate\Routing\Middleware;

use Closure;
use Illuminate\Contracts\Redis\Factory as Redis;
use Illuminate\Redis\Limiters\DurationLimiter;

class ThrottleRequestsWithRedis extends ThrottleRequests
{
    /**
     * The Redis factory implementation.
	 * Redis工厂实现
     *
     * @var \Illuminate\Contracts\Redis\Factory
     */
    protected $redis;

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
     * Create a new request throttler.
	 * 创建新的请求节流
     *
     * @param  \Illuminate\Contracts\Redis\Factory  $redis
     * @return void
     */
    public function __construct(Redis $redis)
    {
        $this->redis = $redis;
    }

    /**
     * Handle an incoming request.
	 * 处理传入请求
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @param  int|string  $maxAttempts
     * @param  float|int  $decayMinutes
     * @param  string  $prefix
     * @return mixed
     *
     * @throws \Symfony\Component\HttpKernel\Exception\HttpException
     */
    public function handle($request, Closure $next, $maxAttempts = 60, $decayMinutes = 1, $prefix = '')
    {
        $key = $prefix.$this->resolveRequestSignature($request);

        $maxAttempts = $this->resolveMaxAttempts($request, $maxAttempts);

        if ($this->tooManyAttempts($key, $maxAttempts, $decayMinutes)) {
            throw $this->buildException($key, $maxAttempts);
        }

        $response = $next($request);

        return $this->addHeaders(
            $response, $maxAttempts,
            $this->calculateRemainingAttempts($key, $maxAttempts)
        );
    }

    /**
     * Determine if the given key has been "accessed" too many times.
	 * 确定给定的键是否被访问了太多次
     *
     * @param  string  $key
     * @param  int  $maxAttempts
     * @param  int  $decayMinutes
     * @return mixed
     */
    protected function tooManyAttempts($key, $maxAttempts, $decayMinutes)
    {
        $limiter = new DurationLimiter(
            $this->redis, $key, $maxAttempts, $decayMinutes * 60
        );

        return tap(! $limiter->acquire(), function () use ($limiter) {
            [$this->decaysAt, $this->remaining] = [
                $limiter->decaysAt, $limiter->remaining,
            ];
        });
    }

    /**
     * Calculate the number of remaining attempts.
	 * 计算剩余的尝试次数
     *
     * @param  string  $key
     * @param  int  $maxAttempts
     * @param  int|null  $retryAfter
     * @return int
     */
    protected function calculateRemainingAttempts($key, $maxAttempts, $retryAfter = null)
    {
        if (is_null($retryAfter)) {
            return $this->remaining;
        }

        return 0;
    }

    /**
     * Get the number of seconds until the lock is released.
	 * 得到锁被释放前的秒数
     *
     * @param  string  $key
     * @return int
     */
    protected function getTimeUntilNextRetry($key)
    {
        return $this->decaysAt - $this->currentTime();
    }
}

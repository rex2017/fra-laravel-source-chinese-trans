<?php
/**
 * 契约，Redis连接接口
 */

namespace Illuminate\Contracts\Redis;

use Closure;

interface Connection
{
    /**
     * Subscribe to a set of given channels for messages.
	 * 订阅给定的通道
     *
     * @param  array|string  $channels
     * @param  \Closure  $callback
     * @return void
     */
    public function subscribe($channels, Closure $callback);

    /**
     * Subscribe to a set of given channels with wildcards.
     *
     * @param  array|string  $channels
     * @param  \Closure  $callback
     * @return void
     */
    public function psubscribe($channels, Closure $callback);

    /**
     * Run a command against the Redis database.
	 * 运行命令
     *
     * @param  string  $method
     * @param  array  $parameters
     * @return mixed
     */
    public function command($method, array $parameters = []);
}

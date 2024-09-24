<?php
/**
 * 广播，Redis广播
 */

namespace Illuminate\Broadcasting\Broadcasters;

use Illuminate\Contracts\Redis\Factory as Redis;
use Illuminate\Support\Arr;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

class RedisBroadcaster extends Broadcaster
{
    use UsePusherChannelConventions;

    /**
     * The Redis instance.
	 * Redis实例
     *
     * @var \Illuminate\Contracts\Redis\Factory
     */
    protected $redis;

    /**
     * The Redis connection to use for broadcasting.
	 * Redis连接用于广播
     *
     * @var string
     */
    protected $connection;

    /**
     * The Redis key prefix.
	 * Redis前缀
     *
     * @var string
     */
    protected $prefix;

    /**
     * Create a new broadcaster instance.
	 * 创建新的广播实例
     *
     * @param  \Illuminate\Contracts\Redis\Factory  $redis
     * @param  string|null  $connection
     * @param  string  $prefix
     * @return void
     */
    public function __construct(Redis $redis, $connection = null, $prefix = '')
    {
        $this->redis = $redis;
        $this->prefix = $prefix;
        $this->connection = $connection;
    }

    /**
     * Authenticate the incoming request for a given channel.
	 * 验证给定通道的传入请求
     *
     * @param  \Illuminate\Http\Request  $request
     * @return mixed
     *
     * @throws \Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException
     */
    public function auth($request)
    {
        $channelName = $this->normalizeChannelName(
            str_replace($this->prefix, '', $request->channel_name)
        );

        if ($this->isGuardedChannel($request->channel_name) &&
            ! $this->retrieveUser($request, $channelName)) {
            throw new AccessDeniedHttpException;
        }

        return parent::verifyUserCanAccessChannel(
            $request, $channelName
        );
    }

    /**
     * Return the valid authentication response.
	 * 返回有效的身份验证响应
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  mixed  $result
     * @return mixed
     */
    public function validAuthenticationResponse($request, $result)
    {
        if (is_bool($result)) {
            return json_encode($result);
        }

        $channelName = $this->normalizeChannelName($request->channel_name);

        return json_encode(['channel_data' => [
            'user_id' => $this->retrieveUser($request, $channelName)->getAuthIdentifier(),
            'user_info' => $result,
        ]]);
    }

    /**
     * Broadcast the given event.、
	 * 广播给定事件
     *
     * @param  array  $channels
     * @param  string  $event
     * @param  array  $payload
     * @return void
     */
    public function broadcast(array $channels, $event, array $payload = [])
    {
        if (empty($channels)) {
            return;
        }

        $connection = $this->redis->connection($this->connection);

        $payload = json_encode([
            'event' => $event,
            'data' => $payload,
            'socket' => Arr::pull($payload, 'socket'),
        ]);

        $connection->eval(
            $this->broadcastMultipleChannelsScript(),
            0, $payload, ...$this->formatChannels($channels)
        );
    }

    /**
     * Get the Lua script for broadcasting to multiple channels.
	 * 得到用于向多个通道广播的Lua脚本
     *
     * ARGV[1] - The payload
     * ARGV[2...] - The channels
     *
     * @return string
     */
    protected function broadcastMultipleChannelsScript()
    {
        return <<<'LUA'
for i = 2, #ARGV do
  redis.call('publish', ARGV[i], ARGV[1])
end
LUA;
    }

    /**
     * Format the channel array into an array of strings.
	 * 格式化通道数组为字符串数组
     *
     * @param  array  $channels
     * @return array
     */
    protected function formatChannels(array $channels)
    {
        return array_map(function ($channel) {
            return $this->prefix.$channel;
        }, parent::formatChannels($channels));
    }
}

<?php
/**
 * 广播，Pusher广播
 */

namespace Illuminate\Broadcasting\Broadcasters;

use Illuminate\Broadcasting\BroadcastException;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Pusher\Pusher;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

class PusherBroadcaster extends Broadcaster
{
    use UsePusherChannelConventions;

    /**
     * The Pusher SDK instance.
	 * Pusher SDK 实例
     *
     * @var \Pusher\Pusher
     */
    protected $pusher;

    /**
     * Create a new broadcaster instance.
	 * 创建新的广播实例
     *
     * @param  \Pusher\Pusher  $pusher
     * @return void
     */
    public function __construct(Pusher $pusher)
    {
        $this->pusher = $pusher;
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
        $channelName = $this->normalizeChannelName($request->channel_name);

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
        if (Str::startsWith($request->channel_name, 'private')) {
            return $this->decodePusherResponse(
                $request, $this->pusher->socket_auth($request->channel_name, $request->socket_id)
            );
        }

        $channelName = $this->normalizeChannelName($request->channel_name);

        return $this->decodePusherResponse(
            $request,
            $this->pusher->presence_auth(
                $request->channel_name, $request->socket_id,
                $this->retrieveUser($request, $channelName)->getAuthIdentifier(), $result
            )
        );
    }

    /**
     * Decode the given Pusher response.
	 * 解码给定的Pusher响应
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  mixed  $response
     * @return array
     */
    protected function decodePusherResponse($request, $response)
    {
        if (! $request->input('callback', false)) {
            return json_decode($response, true);
        }

        return response()->json(json_decode($response, true))
                    ->withCallback($request->callback);
    }

    /**
     * Broadcast the given event.
	 * 广播给定事件
     *
     * @param  array  $channels
     * @param  string  $event
     * @param  array  $payload
     * @return void
     *
     * @throws \Illuminate\Broadcasting\BroadcastException
     */
    public function broadcast(array $channels, $event, array $payload = [])
    {
        $socket = Arr::pull($payload, 'socket');

        $response = $this->pusher->trigger(
            $this->formatChannels($channels), $event, $payload, $socket, true
        );

        if ((is_array($response) && $response['status'] >= 200 && $response['status'] <= 299)
            || $response === true) {
            return;
        }

        throw new BroadcastException(
            ! empty($response['body'])
                ? sprintf('Pusher error: %s.', $response['body'])
                : 'Failed to connect to Pusher.'
        );
    }

    /**
     * Get the Pusher SDK instance.
	 * 得到Pusher SDK实例
     *
     * @return \Pusher\Pusher
     */
    public function getPusher()
    {
        return $this->pusher;
    }
}

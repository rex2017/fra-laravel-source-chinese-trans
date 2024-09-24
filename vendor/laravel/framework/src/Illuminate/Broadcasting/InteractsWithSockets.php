<?php
/**
 * 广播套接字交互特征
 */

namespace Illuminate\Broadcasting;

use Illuminate\Support\Facades\Broadcast;

trait InteractsWithSockets
{
    /**
     * The socket ID for the user that raised the event.
	 * 套接字ID
     *
     * @var string|null
     */
    public $socket;

    /**
     * Exclude the current user from receiving the broadcast.
	 * 执行当前用户的广播
     *
     * @return $this
     */
    public function dontBroadcastToCurrentUser()
    {
        $this->socket = Broadcast::socket();

        return $this;
    }

    /**
     * Broadcast the event to everyone.
	 * 广播事件给每个人
     *
     * @return $this
     */
    public function broadcastToEveryone()
    {
        $this->socket = null;

        return $this;
    }
}

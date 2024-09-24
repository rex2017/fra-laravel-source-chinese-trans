<?php
/**
 * 广播，使用Pusher通道约定
 */

namespace Illuminate\Broadcasting\Broadcasters;

use Illuminate\Support\Str;

trait UsePusherChannelConventions
{
    /**
     * Return true if channel is protected by authentication.
	 * 返回true，如果通道受身份验证保护
     *
     * @param  string  $channel
     * @return bool
     */
    public function isGuardedChannel($channel)
    {
        return Str::startsWith($channel, ['private-', 'presence-']);
    }

    /**
     * Remove prefix from channel name.
	 * 移除通道名前缀
     *
     * @param  string  $channel
     * @return string
     */
    public function normalizeChannelName($channel)
    {
        foreach (['private-encrypted-', 'private-', 'presence-'] as $prefix) {
            if (Str::startsWith($channel, $prefix)) {
                return Str::replaceFirst($prefix, '', $channel);
            }
        }

        return $channel;
    }
}

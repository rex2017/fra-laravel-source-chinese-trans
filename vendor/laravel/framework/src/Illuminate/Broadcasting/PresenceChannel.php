<?php
/**
 * 在线通道
 */

namespace Illuminate\Broadcasting;

class PresenceChannel extends Channel
{
    /**
     * Create a new channel instance.
	 * 创建新的通道实例
     *
     * @param  string  $name
     * @return void
     */
    public function __construct($name)
    {
        parent::__construct('presence-'.$name);
    }
}

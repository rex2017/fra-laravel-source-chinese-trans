<?php
/**
 * 广播私有通道
 */

namespace Illuminate\Broadcasting;

class PrivateChannel extends Channel
{
    /**
     * Create a new channel instance.
	 * 创建新的通道
     *
     * @param  string  $name
     * @return void
     */
    public function __construct($name)
    {
        parent::__construct('private-'.$name);
    }
}

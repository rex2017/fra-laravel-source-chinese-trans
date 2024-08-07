<?php
/**
 * 广播私有频道
 */

namespace Illuminate\Broadcasting;

class PrivateChannel extends Channel
{
    /**
     * Create a new channel instance.
	 * 创建一个新的频道
     *
     * @param  string  $name
     * @return void
     */
    public function __construct($name)
    {
        parent::__construct('private-'.$name);
    }
}

<?php
/**
 * 广播加密私有通道
 */

namespace Illuminate\Broadcasting;

class EncryptedPrivateChannel extends Channel
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
        parent::__construct('private-encrypted-'.$name);
    }
}

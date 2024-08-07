<?php
/**
 * 广播加密私有频道类
 */

namespace Illuminate\Broadcasting;

class EncryptedPrivateChannel extends Channel
{
    /**
     * Create a new channel instance.
	 * 创建一个新的频道实例
     *
     * @param  string  $name
     * @return void
     */
    public function __construct($name)
    {
        parent::__construct('private-encrypted-'.$name);
    }
}

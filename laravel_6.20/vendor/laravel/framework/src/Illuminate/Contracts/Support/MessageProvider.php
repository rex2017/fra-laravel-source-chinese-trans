<?php
/**
 * 契约，信息包提供者接口
 */

namespace Illuminate\Contracts\Support;

interface MessageProvider
{
    /**
     * Get the messages for the instance.
	 * 得到消息包实例
     *
     * @return \Illuminate\Contracts\Support\MessageBag
     */
    public function getMessageBag();
}

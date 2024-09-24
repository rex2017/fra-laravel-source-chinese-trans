<?php
/**
 * 邮件，信息发送事件
 */

namespace Illuminate\Mail\Events;

class MessageSending
{
    /**
     * The Swift message instance.
	 * Swift信息实例
     *
     * @var \Swift_Message
     */
    public $message;

    /**
     * The message data.
	 * 信息数据
     *
     * @var array
     */
    public $data;

    /**
     * Create a new event instance.
	 * 创建新的事件实例
     *
     * @param  \Swift_Message  $message
     * @param  array  $data
     * @return void
     */
    public function __construct($message, $data = [])
    {
        $this->data = $data;
        $this->message = $message;
    }
}

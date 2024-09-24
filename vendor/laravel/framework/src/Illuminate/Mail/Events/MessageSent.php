<?php
/**
 * 邮件，消息发送事件
 */

namespace Illuminate\Mail\Events;

use Swift_Attachment;

class MessageSent
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

    /**
     * Get the serializable representation of the object.
	 * 得到对象的可序列化表示形式
     *
     * @return array
     */
    public function __serialize()
    {
        $hasAttachments = collect($this->message->getChildren())
                                ->whereInstanceOf(Swift_Attachment::class)
                                ->isNotEmpty();

        return $hasAttachments ? [
            'message' => base64_encode(serialize($this->message)),
            'data' => base64_encode(serialize($this->data)),
            'hasAttachments' => true,
        ] : [
            'message' => $this->message,
            'data' => $this->data,
            'hasAttachments' => false,
        ];
    }

    /**
     * Marshal the object from its serialized data.
	 * 封装对象从对象的序列化数据
     *
     * @param  array  $data
     * @return void
     */
    public function __unserialize(array $data)
    {
        if (isset($data['hasAttachments']) && $data['hasAttachments'] === true) {
            $this->message = unserialize(base64_decode($data['message']));
            $this->data = unserialize(base64_decode($data['data']));
        } else {
            $this->message = $data['message'];
            $this->data = $data['data'];
        }
    }
}

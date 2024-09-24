<?php
/**
 * 邮件，数组传输
 */

namespace Illuminate\Mail\Transport;

use Illuminate\Support\Collection;
use Swift_Mime_SimpleMessage;

class ArrayTransport extends Transport
{
    /**
     * The collection of Swift Messages.
	 * Swift消息的集合
     *
     * @var \Illuminate\Support\Collection
     */
    protected $messages;

    /**
     * Create a new array transport instance.
	 * 创建新的数据传输实例
     *
     * @return void
     */
    public function __construct()
    {
        $this->messages = new Collection;
    }

    /**
     * {@inheritdoc}
     */
    public function send(Swift_Mime_SimpleMessage $message, &$failedRecipients = null)
    {
        $this->beforeSendPerformed($message);

        $this->messages[] = $message;

        return $this->numberOfRecipients($message);
    }

    /**
     * Retrieve the collection of messages.
	 * 检索信息合集
     *
     * @return \Illuminate\Support\Collection
     */
    public function messages()
    {
        return $this->messages;
    }

    /**
     * Clear all of the messages from the local collection.
	 * 清除所有消息从本地集合
     *
     * @return \Illuminate\Support\Collection
     */
    public function flush()
    {
        return $this->messages = new Collection;
    }
}

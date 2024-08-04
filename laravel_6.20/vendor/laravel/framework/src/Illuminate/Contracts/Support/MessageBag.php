<?php
/**
 * 契约，消息包接口
 */

namespace Illuminate\Contracts\Support;

interface MessageBag extends Arrayable
{
    /**
     * Get the keys present in the message bag.
	 * 得到消息包主键
     *
     * @return array
     */
    public function keys();

    /**
     * Add a message to the bag.
	 * 添加信息
     *
     * @param  string  $key
     * @param  string  $message
     * @return $this
     */
    public function add($key, $message);

    /**
     * Merge a new array of messages into the bag.
	 * 合并新消息
     *
     * @param  \Illuminate\Contracts\Support\MessageProvider|array  $messages
     * @return $this
     */
    public function merge($messages);

    /**
     * Determine if messages exist for a given key.
	 * 确定是否消息存在
     *
     * @param  string|array  $key
     * @return bool
     */
    public function has($key);

    /**
     * Get the first message from the bag for a given key.
	 * 得到第一个消息
     *
     * @param  string|null  $key
     * @param  string|null  $format
     * @return string
     */
    public function first($key = null, $format = null);

    /**
     * Get all of the messages from the bag for a given key.
	 * 得到消息
     *
     * @param  string  $key
     * @param  string|null  $format
     * @return array
     */
    public function get($key, $format = null);

    /**
     * Get all of the messages for every key in the bag.
	 * 得到所有的消息
     *
     * @param  string|null  $format
     * @return array
     */
    public function all($format = null);

    /**
     * Get the raw messages in the container.
	 * 得到消息
     *
     * @return array
     */
    public function getMessages();

    /**
     * Get the default message format.
	 * 得到消息格式
     *
     * @return string
     */
    public function getFormat();

    /**
     * Set the default message format.
	 * 设置默认消息格式 
     *
     * @param  string  $format
     * @return $this
     */
    public function setFormat($format = ':message');

    /**
     * Determine if the message bag has any messages.
	 * 确定消息是否空
     *
     * @return bool
     */
    public function isEmpty();

    /**
     * Determine if the message bag has any messages.
	 * 确定消息是否非空
     *
     * @return bool
     */
    public function isNotEmpty();

    /**
     * Get the number of messages in the container.
	 * 得到消息个数
     *
     * @return int
     */
    public function count();
}

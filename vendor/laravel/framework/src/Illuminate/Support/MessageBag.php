<?php
/**
 * 支持，消息包
 */

namespace Illuminate\Support;

use Countable;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Contracts\Support\Jsonable;
use Illuminate\Contracts\Support\MessageBag as MessageBagContract;
use Illuminate\Contracts\Support\MessageProvider;
use JsonSerializable;

class MessageBag implements Arrayable, Countable, Jsonable, JsonSerializable, MessageBagContract, MessageProvider
{
    /**
     * All of the registered messages.
	 * 所有已注册消息
     *
     * @var array
     */
    protected $messages = [];

    /**
     * Default format for message output.
	 * 消息输出的默认格式
     *
     * @var string
     */
    protected $format = ':message';

    /**
     * Create a new message bag instance.
	 * 创建新的消息包实例
     *
     * @param  array  $messages
     * @return void
     */
    public function __construct(array $messages = [])
    {
        foreach ($messages as $key => $value) {
            $value = $value instanceof Arrayable ? $value->toArray() : (array) $value;

            $this->messages[$key] = array_unique($value);
        }
    }

    /**
     * Get the keys present in the message bag.
	 * 把钥匙放在留言袋里
     *
     * @return array
     */
    public function keys()
    {
        return array_keys($this->messages);
    }

    /**
     * Add a message to the message bag.
	 * 添加消息至消息包
     *
     * @param  string  $key
     * @param  string  $message
     * @return $this
     */
    public function add($key, $message)
    {
        if ($this->isUnique($key, $message)) {
            $this->messages[$key][] = $message;
        }

        return $this;
    }

    /**
     * Determine if a key and message combination already exists.
	 * 确定是否已经存在一个密钥和消息组合
     *
     * @param  string  $key
     * @param  string  $message
     * @return bool
     */
    protected function isUnique($key, $message)
    {
        $messages = (array) $this->messages;

        return ! isset($messages[$key]) || ! in_array($message, $messages[$key]);
    }

    /**
     * Merge a new array of messages into the message bag.
	 * 合并新的消息数组到消息包中
     *
     * @param  \Illuminate\Contracts\Support\MessageProvider|array  $messages
     * @return $this
     */
    public function merge($messages)
    {
        if ($messages instanceof MessageProvider) {
            $messages = $messages->getMessageBag()->getMessages();
        }

        $this->messages = array_merge_recursive($this->messages, $messages);

        return $this;
    }

    /**
     * Determine if messages exist for all of the given keys.
	 * 确定是否存在所有给定键的消息
     *
     * @param  array|string  $key
     * @return bool
     */
    public function has($key)
    {
        if ($this->isEmpty()) {
            return false;
        }

        if (is_null($key)) {
            return $this->any();
        }

        $keys = is_array($key) ? $key : func_get_args();

        foreach ($keys as $key) {
            if ($this->first($key) === '') {
                return false;
            }
        }

        return true;
    }

    /**
     * Determine if messages exist for any of the given keys.
	 * 确定是否存在任何给定键的消息
     *
     * @param  array|string  $keys
     * @return bool
     */
    public function hasAny($keys = [])
    {
        if ($this->isEmpty()) {
            return false;
        }

        $keys = is_array($keys) ? $keys : func_get_args();

        foreach ($keys as $key) {
            if ($this->has($key)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get the first message from the message bag for a given key.
	 * 得到给定键的第一条消息从消息包中
     *
     * @param  string|null  $key
     * @param  string|null  $format
     * @return string
     */
    public function first($key = null, $format = null)
    {
        $messages = is_null($key) ? $this->all($format) : $this->get($key, $format);

        $firstMessage = Arr::first($messages, null, '');

        return is_array($firstMessage) ? Arr::first($firstMessage) : $firstMessage;
    }

    /**
     * Get all of the messages from the message bag for a given key.
	 * 得到给定键的所有消息从消息包中
     *
     * @param  string  $key
     * @param  string  $format
     * @return array
     */
    public function get($key, $format = null)
    {
        // If the message exists in the message bag, we will transform it and return
        // the message. Otherwise, we will check if the key is implicit & collect
        // all the messages that match the given key and output it as an array.
		// 如果消息包中存在消息，我们将对其进行转换并返回消息。
		// 否则，我们将检查键是否是隐式的，并收集与给定键匹配的所有消息，并将其作为数组输出。
        if (array_key_exists($key, $this->messages)) {
            return $this->transform(
                $this->messages[$key], $this->checkFormat($format), $key
            );
        }

        if (Str::contains($key, '*')) {
            return $this->getMessagesForWildcardKey($key, $format);
        }

        return [];
    }

    /**
     * Get the messages for a wildcard key.
	 * 得到通配符键的消息
     *
     * @param  string  $key
     * @param  string|null  $format
     * @return array
     */
    protected function getMessagesForWildcardKey($key, $format)
    {
        return collect($this->messages)
                ->filter(function ($messages, $messageKey) use ($key) {
                    return Str::is($key, $messageKey);
                })
                ->map(function ($messages, $messageKey) use ($format) {
                    return $this->transform(
                        $messages, $this->checkFormat($format), $messageKey
                    );
                })->all();
    }

    /**
     * Get all of the messages for every key in the message bag.
	 * 得到消息包中每个键的所有消息
     *
     * @param  string  $format
     * @return array
     */
    public function all($format = null)
    {
        $format = $this->checkFormat($format);

        $all = [];

        foreach ($this->messages as $key => $messages) {
            $all = array_merge($all, $this->transform($messages, $format, $key));
        }

        return $all;
    }

    /**
     * Get all of the unique messages for every key in the message bag.
	 * 得到消息包中每个键的所有唯一消息
     *
     * @param  string  $format
     * @return array
     */
    public function unique($format = null)
    {
        return array_unique($this->all($format));
    }

    /**
     * Format an array of messages.
	 * 格式化消息数组
     *
     * @param  array  $messages
     * @param  string  $format
     * @param  string  $messageKey
     * @return array
     */
    protected function transform($messages, $format, $messageKey)
    {
        return collect((array) $messages)
            ->map(function ($message) use ($format, $messageKey) {
                // We will simply spin through the given messages and transform each one
                // replacing the :message place holder with the real message allowing
                // the messages to be easily formatted to each developer's desires.
				// 我们将简单地旋转给定的消息并转换每个消息，将：message占位符替换为真实消息，
				// 从而可以根据每个开发人员的需求轻松格式化消息。
                return str_replace([':message', ':key'], [$message, $messageKey], $format);
            })->all();
    }

    /**
     * Get the appropriate format based on the given format.
	 * 得到给定的格式获取适当的格式
     *
     * @param  string  $format
     * @return string
     */
    protected function checkFormat($format)
    {
        return $format ?: $this->format;
    }

    /**
     * Get the raw messages in the message bag.
	 * 得到原始消息在消息包中
     *
     * @return array
     */
    public function messages()
    {
        return $this->messages;
    }

    /**
     * Get the raw messages in the message bag.
	 * 得到原始消息在消息包中
     *
     * @return array
     */
    public function getMessages()
    {
        return $this->messages();
    }

    /**
     * Get the messages for the instance.
	 * 得到实例的消息
     *
     * @return \Illuminate\Support\MessageBag
     */
    public function getMessageBag()
    {
        return $this;
    }

    /**
     * Get the default message format.
	 * 得到默认消息格式
     *
     * @return string
     */
    public function getFormat()
    {
        return $this->format;
    }

    /**
     * Set the default message format.
	 * 设置默认消息格式
     *
     * @param  string  $format
     * @return \Illuminate\Support\MessageBag
     */
    public function setFormat($format = ':message')
    {
        $this->format = $format;

        return $this;
    }

    /**
     * Determine if the message bag has any messages.
	 * 确定消息包中是否有任何消息
     *
     * @return bool
     */
    public function isEmpty()
    {
        return ! $this->any();
    }

    /**
     * Determine if the message bag has any messages.
	 * 确定消息包中是否有任何消息
     *
     * @return bool
     */
    public function isNotEmpty()
    {
        return $this->any();
    }

    /**
     * Determine if the message bag has any messages.
	 * 确定消息包中是否有任何消息
     *
     * @return bool
     */
    public function any()
    {
        return $this->count() > 0;
    }

    /**
     * Get the number of messages in the message bag.
	 * 得到消息包中的消息数
     *
     * @return int
     */
    public function count()
    {
        return count($this->messages, COUNT_RECURSIVE) - count($this->messages);
    }

    /**
     * Get the instance as an array.
	 * 得到实例为数组
     *
     * @return array
     */
    public function toArray()
    {
        return $this->getMessages();
    }

    /**
     * Convert the object into something JSON serializable.
	 * 转换对象为可序列化JSON
     *
     * @return array
     */
    public function jsonSerialize()
    {
        return $this->toArray();
    }

    /**
     * Convert the object to its JSON representation.
	 * 转换对象为其JSON表示形式
     *
     * @param  int  $options
     * @return string
     */
    public function toJson($options = 0)
    {
        return json_encode($this->jsonSerialize(), $options);
    }

    /**
     * Convert the message bag to its string representation.
	 * 转换消息包为其字符串表示形式
     *
     * @return string
     */
    public function __toString()
    {
        return $this->toJson();
    }
}

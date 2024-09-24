<?php
/**
 * 缓存，密钥写入
 */

namespace Illuminate\Cache\Events;

class KeyWritten extends CacheEvent
{
    /**
     * The value that was written.
	 * 写入的值
     *
     * @var mixed
     */
    public $value;

    /**
     * The number of seconds the key should be valid.
	 * 密钥有效的秒数
     *
     * @var int|null
     */
    public $seconds;

    /**
     * Create a new event instance.
	 * 创建新的事件实例
     *
     * @param  string  $key
     * @param  mixed  $value
     * @param  int|null  $seconds
     * @param  array  $tags
     * @return void
     */
    public function __construct($key, $value, $seconds = null, $tags = [])
    {
        parent::__construct($key, $tags);

        $this->value = $value;
        $this->seconds = $seconds;
    }
}

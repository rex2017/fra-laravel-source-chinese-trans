<?php
/**
 * 缓存，缓存事件
 */

namespace Illuminate\Cache\Events;

abstract class CacheEvent
{
    /**
     * The key of the event.
	 * 事件密钥
     *
     * @var string
     */
    public $key;

    /**
     * The tags that were assigned to the key.
	 * 分配给密钥的标签
     *
     * @var array
     */
    public $tags;

    /**
     * Create a new event instance.
	 * 创建新的事件实例
     *
     * @param  string  $key
     * @param  array  $tags
     * @return void
     */
    public function __construct($key, array $tags = [])
    {
        $this->key = $key;
        $this->tags = $tags;
    }

    /**
     * Set the tags for the cache event.
	 * 设置缓存事件的标记
     *
     * @param  array  $tags
     * @return $this
     */
    public function setTags($tags)
    {
        $this->tags = $tags;

        return $this;
    }
}

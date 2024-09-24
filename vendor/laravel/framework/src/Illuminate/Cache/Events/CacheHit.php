<?php
/**
 * 缓存，缓存命中，当请求的数据在缓存中被找到时，称为缓存命中。
 */

namespace Illuminate\Cache\Events;

class CacheHit extends CacheEvent
{
    /**
     * The value that was retrieved.
	 * 检索到的值
     *
     * @var mixed
     */
    public $value;

    /**
     * Create a new event instance.
	 * 创建新的事件实例
     *
     * @param  string  $key
     * @param  mixed  $value
     * @param  array  $tags
     * @return void
     */
    public function __construct($key, $value, array $tags = [])
    {
        parent::__construct($key, $tags);

        $this->value = $value;
    }
}

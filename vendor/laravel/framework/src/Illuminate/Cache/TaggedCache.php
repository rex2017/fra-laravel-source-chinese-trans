<?php
/**
 * 可标记缓存
 */

namespace Illuminate\Cache;

use Illuminate\Contracts\Cache\Store;

class TaggedCache extends Repository
{
    use RetrievesMultipleKeys {
        putMany as putManyAlias;
    }

    /**
     * The tag set instance.
	 * 标记设置实例
     *
     * @var \Illuminate\Cache\TagSet
     */
    protected $tags;

    /**
     * Create a new tagged cache instance.
	 * 创建新的标记缓存实例
     *
     * @param  \Illuminate\Contracts\Cache\Store  $store
     * @param  \Illuminate\Cache\TagSet  $tags
     * @return void
     */
    public function __construct(Store $store, TagSet $tags)
    {
        parent::__construct($store);

        $this->tags = $tags;
    }

    /**
     * Store multiple items in the cache for a given number of seconds.
	 * 存储多个项目至缓存中使用给定秒数
     *
     * @param  array  $values
     * @param  int|null  $ttl
     * @return bool
     */
    public function putMany(array $values, $ttl = null)
    {
        if ($ttl === null) {
            return $this->putManyForever($values);
        }

        return $this->putManyAlias($values, $ttl);
    }

    /**
     * Increment the value of an item in the cache.
	 * 增加缓存中项的值
     *
     * @param  string  $key
     * @param  mixed  $value
     * @return void
     */
    public function increment($key, $value = 1)
    {
        $this->store->increment($this->itemKey($key), $value);
    }

    /**
     * Decrement the value of an item in the cache.
	 * 递减缓存中项的值
     *
     * @param  string  $key
     * @param  mixed  $value
     * @return void
     */
    public function decrement($key, $value = 1)
    {
        $this->store->decrement($this->itemKey($key), $value);
    }

    /**
     * Remove all items from the cache.
	 * 清空缓存中所有项目
     *
     * @return bool
     */
    public function flush()
    {
        $this->tags->reset();

        return true;
    }

    /**
     * {@inheritdoc}
     */
    protected function itemKey($key)
    {
        return $this->taggedItemKey($key);
    }

    /**
     * Get a fully qualified key for a tagged item.
	 * 得到标记项的完全限定键
     *
     * @param  string  $key
     * @return string
     */
    public function taggedItemKey($key)
    {
        return sha1($this->tags->getNamespace()).':'.$key;
    }

    /**
     * Fire an event for this cache instance.
	 * 触发此缓存实例的事件
     *
     * @param  string  $event
     * @return void
     */
    protected function event($event)
    {
        parent::event($event->setTags($this->tags->getNames()));
    }

    /**
     * Get the tag set instance.
	 * 得到标记集实例
     *
     * @return \Illuminate\Cache\TagSet
     */
    public function getTags()
    {
        return $this->tags;
    }
}

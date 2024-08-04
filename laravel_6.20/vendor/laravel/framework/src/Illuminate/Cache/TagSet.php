<?php
/**
 * 缓存标签集类
 */

namespace Illuminate\Cache;

use Illuminate\Contracts\Cache\Store;

class TagSet
{
    /**
     * The cache store implementation.
	 * 缓存存储接口
     *
     * @var \Illuminate\Contracts\Cache\Store
     */
    protected $store;

    /**
     * The tag names.
	 * 标签名
     *
     * @var array
     */
    protected $names = [];

    /**
     * Create a new TagSet instance.
	 * 创建一个新标签集实例
     *
     * @param  \Illuminate\Contracts\Cache\Store  $store
     * @param  array  $names
     * @return void
     */
    public function __construct(Store $store, array $names = [])
    {
        $this->store = $store;
        $this->names = $names;
    }

    /**
     * Reset all tags in the set.
	 * 重置所有标签
     *
     * @return void
     */
    public function reset()
    {
        array_walk($this->names, [$this, 'resetTag']);
    }

    /**
     * Reset the tag and return the new tag identifier.
     *
     * @param  string  $name
     * @return string
     */
    public function resetTag($name)
    {
        $this->store->forever($this->tagKey($name), $id = str_replace('.', '', uniqid('', true)));

        return $id;
    }

    /**
     * Get a unique namespace that changes when any of the tags are flushed.
	 * 得到唯一命名空间
     *
     * @return string
     */
    public function getNamespace()
    {
        return implode('|', $this->tagIds());
    }

    /**
     * Get an array of tag identifiers for all of the tags in the set.
     *
     * @return array
     */
    protected function tagIds()
    {
        return array_map([$this, 'tagId'], $this->names);
    }

    /**
     * Get the unique tag identifier for a given tag.
     *
     * @param  string  $name
     * @return string
     */
    public function tagId($name)
    {
        return $this->store->get($this->tagKey($name)) ?: $this->resetTag($name);
    }

    /**
     * Get the tag identifier key for a given tag.
     *
     * @param  string  $name
     * @return string
     */
    public function tagKey($name)
    {
        return 'tag:'.$name.':key';
    }

    /**
     * Get all of the tag names in the set.
	 * 得到设置中所有名称
     *
     * @return array
     */
    public function getNames()
    {
        return $this->names;
    }
}

<?php
/**
 * 文件系统Cache
 */

namespace Illuminate\Filesystem;

use Illuminate\Contracts\Cache\Repository;
use League\Flysystem\Cached\Storage\AbstractCache;

class Cache extends AbstractCache
{
    /**
     * The cache repository implementation.
	 * 缓存资源库接口
     *
     * @var \Illuminate\Contracts\Cache\Repository
     */
    protected $repository;

    /**
     * The cache key.
	 * 缓存KEY
     *
     * @var string
     */
    protected $key;

    /**
     * The cache expiration time in seconds.
	 ×　缓存超时秒
     *
     * @var int|null
     */
    protected $expire;

    /**
     * Create a new cache instance.
	 * 创建新的缓存实例
     *
     * @param  \Illuminate\Contracts\Cache\Repository  $repository
     * @param  string  $key
     * @param  int|null  $expire
     * @return void
     */
    public function __construct(Repository $repository, $key = 'flysystem', $expire = null)
    {
        $this->key = $key;
        $this->expire = $expire;
        $this->repository = $repository;
    }

    /**
     * Load the cache.
	 * 加载缓存
     *
     * @return void
     */
    public function load()
    {
        $contents = $this->repository->get($this->key);

        if (! is_null($contents)) {
            $this->setFromStorage($contents);
        }
    }

    /**
     * Persist the cache.
	 * 持久化缓存
     *
     * @return void
     */
    public function save()
    {
        $contents = $this->getForStorage();

        $this->repository->put($this->key, $contents, $this->expire);
    }
}

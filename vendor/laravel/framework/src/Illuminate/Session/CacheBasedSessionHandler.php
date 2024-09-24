<?php
/**
 * 基于缓存的会话处理程序
 */

namespace Illuminate\Session;

use Illuminate\Contracts\Cache\Repository as CacheContract;
use SessionHandlerInterface;

class CacheBasedSessionHandler implements SessionHandlerInterface
{
    /**
     * The cache repository instance.
	 * 缓存仓库实例
     *
     * @var \Illuminate\Contracts\Cache\Repository
     */
    protected $cache;

    /**
     * The number of minutes to store the data in the cache.
	 * 将数据存储在缓存中的分钟数
     *
     * @var int
     */
    protected $minutes;

    /**
     * Create a new cache driven handler instance.
	 * 创建新的缓存驱动处理实例
     *
     * @param  \Illuminate\Contracts\Cache\Repository  $cache
     * @param  int  $minutes
     * @return void
     */
    public function __construct(CacheContract $cache, $minutes)
    {
        $this->cache = $cache;
        $this->minutes = $minutes;
    }

    /**
     * {@inheritdoc}
     */
    public function open($savePath, $sessionName)
    {
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function close()
    {
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function read($sessionId)
    {
        return $this->cache->get($sessionId, '');
    }

    /**
     * {@inheritdoc}
     */
    public function write($sessionId, $data)
    {
        return $this->cache->put($sessionId, $data, $this->minutes * 60);
    }

    /**
     * {@inheritdoc}
     */
    public function destroy($sessionId)
    {
        return $this->cache->forget($sessionId);
    }

    /**
     * {@inheritdoc}
     */
    public function gc($lifetime)
    {
        return true;
    }

    /**
     * Get the underlying cache repository.
	 * 得到底层缓存存储库
     *
     * @return \Illuminate\Contracts\Cache\Repository
     */
    public function getCache()
    {
        return $this->cache;
    }
}

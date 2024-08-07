<?php
/**
 * 契约，缓存工厂接口
 */

namespace Illuminate\Contracts\Cache;

interface Factory
{
    /**
     * Get a cache store instance by name.
	 * 按名称获得缓存存储实例
     *
     * @param  string|null  $name
     * @return \Illuminate\Contracts\Cache\Repository
     */
    public function store($name = null);
}

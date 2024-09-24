<?php
/**
 * 契约，缓存工厂接口
 */

namespace Illuminate\Contracts\Cache;

interface Factory
{
    /**
     * Get a cache store instance by name.
	 * 得到缓存存储实例按名称
     *
     * @param  string|null  $name
     * @return \Illuminate\Contracts\Cache\Repository
     */
    public function store($name = null);
}

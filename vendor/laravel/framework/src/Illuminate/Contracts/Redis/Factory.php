<?php
/**
 * 契约，Redis工厂接口
 */


namespace Illuminate\Contracts\Redis;

interface Factory
{
    /**
     * Get a Redis connection by name.
	 * 得到一个Redis连接
     *
     * @param  string|null  $name
     * @return \Illuminate\Redis\Connections\Connection
     */
    public function connection($name = null);
}

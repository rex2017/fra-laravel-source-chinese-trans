<?php
/**
 * 契约，管道路由接口
 */

namespace Illuminate\Contracts\Pipeline;

interface Hub
{
    /**
     * Send an object through one of the available pipelines.
	 * 发送一个对象通过可用管道
     *
     * @param  mixed  $object
     * @param  string|null  $pipeline
     * @return mixed
     */
    public function pipe($object, $pipeline = null);
}

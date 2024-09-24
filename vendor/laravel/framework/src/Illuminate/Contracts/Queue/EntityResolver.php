<?php
/**
 * 契约，队列实体解析接口
 */

namespace Illuminate\Contracts\Queue;

interface EntityResolver
{
    /**
     * Resolve the entity for the given ID.
	 * 解析给定ID的实体
     *
     * @param  string  $type
     * @param  mixed  $id
     * @return mixed
     */
    public function resolve($type, $id);
}

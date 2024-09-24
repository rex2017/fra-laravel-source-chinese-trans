<?php
/**
 * 契约，可用数组接口
 */

namespace Illuminate\Contracts\Support;

interface Arrayable
{
    /**
     * Get the instance as an array.
	 * 得到实例数组
     *
     * @return array
     */
    public function toArray();
}

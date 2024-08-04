<?php
/**
 * 契约，广播工厂接口
 */

namespace Illuminate\Contracts\Broadcasting;

interface Factory
{
    /**
     * Get a broadcaster implementation by name.
	 * 得到一个广播实现按名称
     *
     * @param  string|null  $name
     * @return void
     */
    public function connection($name = null);
}

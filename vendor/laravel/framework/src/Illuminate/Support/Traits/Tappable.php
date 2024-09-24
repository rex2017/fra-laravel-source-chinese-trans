<?php
/**
 * 支持，可开采的
 */

namespace Illuminate\Support\Traits;

trait Tappable
{
    /**
     * Call the given Closure with this instance then return the instance.
	 * 使用此实例调用给定的Closure，然后返回该实例。
     *
     * @param  callable|null  $callback
     * @return mixed
     */
    public function tap($callback = null)
    {
        return tap($this, $callback);
    }
}

<?php
/**
 * 支持，高级收集代理
 */

namespace Illuminate\Support;

class HigherOrderTapProxy
{
    /**
     * The target being tapped.
	 * 被监听的目标
     *
     * @var mixed
     */
    public $target;

    /**
     * Create a new tap proxy instance.
	 * 创建新的tap代理实例
     *
     * @param  mixed  $target
     * @return void
     */
    public function __construct($target)
    {
        $this->target = $target;
    }

    /**
     * Dynamically pass method calls to the target.
	 * 动态地将方法调用传递给目标
     *
     * @param  string  $method
     * @param  array  $parameters
     * @return mixed
     */
    public function __call($method, $parameters)
    {
        $this->target->{$method}(...$parameters);

        return $this->target;
    }
}

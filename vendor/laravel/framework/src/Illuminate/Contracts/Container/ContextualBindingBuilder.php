<?php
/**
 * 契约，容器上下文绑定生成器接口
 */

namespace Illuminate\Contracts\Container;

interface ContextualBindingBuilder
{
    /**
     * Define the abstract target that depends on the context.
	 * 定义依赖于上下文的抽象目标
     *
     * @param  string  $abstract
     * @return $this
     */
    public function needs($abstract);

    /**
     * Define the implementation for the contextual binding.
	 * 定义上下文绑定的实现
     *
     * @param  \Closure|string  $implementation
     * @return void
     */
    public function give($implementation);
}

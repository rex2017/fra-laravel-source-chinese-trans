<?php
/**
 * 容器上下文绑定生成器
 */

namespace Illuminate\Container;

use Illuminate\Contracts\Container\Container;
use Illuminate\Contracts\Container\ContextualBindingBuilder as ContextualBindingBuilderContract;

class ContextualBindingBuilder implements ContextualBindingBuilderContract
{
    /**
     * The underlying container instance.
	 * 底层容器实例
     *
     * @var \Illuminate\Contracts\Container\Container
     */
    protected $container;

    /**
     * The concrete instance.
	 * 具体实例
     *
     * @var string|array
     */
    protected $concrete;

    /**
     * The abstract target.
	 * 抽象类目标
     *
     * @var string
     */
    protected $needs;

    /**
     * Create a new contextual binding builder.
	 * 创建新的上下文绑定生成器
     *
     * @param  \Illuminate\Contracts\Container\Container  $container
     * @param  string|array  $concrete
     * @return void
     */
    public function __construct(Container $container, $concrete)
    {
        $this->concrete = $concrete;
        $this->container = $container;
    }

    /**
     * Define the abstract target that depends on the context.
	 * 定义依赖于上下文的抽象目标
     *
     * @param  string  $abstract
     * @return $this
     */
    public function needs($abstract)
    {
        $this->needs = $abstract;

        return $this;
    }

    /**
     * Define the implementation for the contextual binding.
	 * 定义上下文绑定的实现
     *
     * @param  \Closure|string  $implementation
     * @return void
     */
    public function give($implementation)
    {
        foreach (Util::arrayWrap($this->concrete) as $concrete) {
            $this->container->addContextualBinding($concrete, $this->needs, $implementation);
        }
    }
}

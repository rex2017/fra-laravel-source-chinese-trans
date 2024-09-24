<?php
/**
 * 契约，容器接口
 */

namespace Illuminate\Contracts\Container;

use Closure;
use Psr\Container\ContainerInterface;

interface Container extends ContainerInterface
{
    /**
     * Determine if the given abstract type has been bound.
	 * 确定抽象类是否已被绑定
     *
     * @param  string  $abstract
     * @return bool
     */
    public function bound($abstract);

    /**
     * Alias a type to a different name.
	 * 别名类型为不同的名称
     *
     * @param  string  $abstract
     * @param  string  $alias
     * @return void
     *
     * @throws \LogicException
     */
    public function alias($abstract, $alias);

    /**
     * Assign a set of tags to a given binding.
	 * 分配一组标记为给定的绑定
     *
     * @param  array|string  $abstracts
     * @param  array|mixed  ...$tags
     * @return void
     */
    public function tag($abstracts, $tags);

    /**
     * Resolve all of the bindings for a given tag.
	 * 解析给定标记的所有绑定
     *
     * @param  string  $tag
     * @return iterable
     */
    public function tagged($tag);

    /**
     * Register a binding with the container.
	 * 注册绑定至容器
     *
     * @param  string  $abstract
     * @param  \Closure|string|null  $concrete
     * @param  bool  $shared
     * @return void
     */
    public function bind($abstract, $concrete = null, $shared = false);

    /**
     * Register a binding if it hasn't already been registered.
	 * 注册绑定是否没有注册
     *
     * @param  string  $abstract
     * @param  \Closure|string|null  $concrete
     * @param  bool  $shared
     * @return void
     */
    public function bindIf($abstract, $concrete = null, $shared = false);

    /**
     * Register a shared binding in the container.
	 * 注册一个共享绑定在容器中
     *
     * @param  string  $abstract
     * @param  \Closure|string|null  $concrete
     * @return void
     */
    public function singleton($abstract, $concrete = null);

    /**
     * Register a shared binding if it hasn't already been registered.
	 * 注册一个共享绑定如果尚未注册共享绑定
     *
     * @param  string  $abstract
     * @param  \Closure|string|null  $concrete
     * @return void
     */
    public function singletonIf($abstract, $concrete = null);

    /**
     * "Extend" an abstract type in the container.
	 * 扩展容器中的抽象类型
     *
     * @param  string  $abstract
     * @param  \Closure  $closure
     * @return void
     *
     * @throws \InvalidArgumentException
     */
    public function extend($abstract, Closure $closure);

    /**
     * Register an existing instance as shared in the container.
	 * 注册一个已存在的实例为共享在容器中
     *
     * @param  string  $abstract
     * @param  mixed  $instance
     * @return mixed
     */
    public function instance($abstract, $instance);

    /**
     * Add a contextual binding to the container.
	 * 添加上下文绑定至容器
     *
     * @param  string  $concrete
     * @param  string  $abstract
     * @param  \Closure|string  $implementation
     * @return void
     */
    public function addContextualBinding($concrete, $abstract, $implementation);

    /**
     * Define a contextual binding.
	 * 定义上下文绑定
     *
     * @param  string|array  $concrete
     * @return \Illuminate\Contracts\Container\ContextualBindingBuilder
     */
    public function when($concrete);

    /**
     * Get a closure to resolve the given type from the container.
	 * 得到闭包并从容器中解析给定类型
     *
     * @param  string  $abstract
     * @return \Closure
     */
    public function factory($abstract);

    /**
     * Flush the container of all bindings and resolved instances.
	 * 清空容器的所有绑定和已解析实例
     *
     * @return void
     */
    public function flush();

    /**
     * Resolve the given type from the container.
	 * 解析给定的抽象类从容器中
     *
     * @param  string  $abstract
     * @param  array  $parameters
     * @return mixed
     *
     * @throws \Illuminate\Contracts\Container\BindingResolutionException
     */
    public function make($abstract, array $parameters = []);

    /**
     * Call the given Closure / class@method and inject its dependencies.
	 * 调取给定闭包并注入依赖
     *
     * @param  callable|string  $callback
     * @param  array  $parameters
     * @param  string|null  $defaultMethod
     * @return mixed
     */
    public function call($callback, array $parameters = [], $defaultMethod = null);

    /**
     * Determine if the given abstract type has been resolved.
	 * 确定给定的抽象类型是否已被解析
     *
     * @param  string  $abstract
     * @return bool
     */
    public function resolved($abstract);

    /**
     * Register a new resolving callback.
	 * 注册一个新的解析回调
     *
     * @param  \Closure|string  $abstract
     * @param  \Closure|null  $callback
     * @return void
     */
    public function resolving($abstract, Closure $callback = null);

    /**
     * Register a new after resolving callback.
	 * 注册一个新的在解析回调后
     *
     * @param  \Closure|string  $abstract
     * @param  \Closure|null  $callback
     * @return void
     */
    public function afterResolving($abstract, Closure $callback = null);
}

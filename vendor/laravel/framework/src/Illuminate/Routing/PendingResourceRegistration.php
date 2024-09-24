<?php
/**
 * 路由待处理资源注册
 */

namespace Illuminate\Routing;

use Illuminate\Support\Traits\Macroable;

class PendingResourceRegistration
{
    use Macroable;

    /**
     * The resource registrar.
	 * 资源注册
     *
     * @var \Illuminate\Routing\ResourceRegistrar
     */
    protected $registrar;

    /**
     * The resource name.
	 * 资源名称
     *
     * @var string
     */
    protected $name;

    /**
     * The resource controller.
	 * 资源控制器
     *
     * @var string
     */
    protected $controller;

    /**
     * The resource options.
	 * 资源操作
     *
     * @var array
     */
    protected $options = [];

    /**
     * The resource's registration status.
	 * 资源注册状态
     *
     * @var bool
     */
    protected $registered = false;

    /**
     * Create a new pending resource registration instance.
	 & 创建新的资源注册实例
     *
     * @param  \Illuminate\Routing\ResourceRegistrar  $registrar
     * @param  string  $name
     * @param  string  $controller
     * @param  array  $options
     * @return void
     */
    public function __construct(ResourceRegistrar $registrar, $name, $controller, array $options)
    {
        $this->name = $name;
        $this->options = $options;
        $this->registrar = $registrar;
        $this->controller = $controller;
    }

    /**
     * Set the methods the controller should apply to.
	 * 设置控制器应该接受的方法
     *
     * @param  array|string|dynamic  $methods
     * @return \Illuminate\Routing\PendingResourceRegistration
     */
    public function only($methods)
    {
        $this->options['only'] = is_array($methods) ? $methods : func_get_args();

        return $this;
    }

    /**
     * Set the methods the controller should exclude.
	 * 设置控制器应该排除的方法
     *
     * @param  array|string|dynamic  $methods
     * @return \Illuminate\Routing\PendingResourceRegistration
     */
    public function except($methods)
    {
        $this->options['except'] = is_array($methods) ? $methods : func_get_args();

        return $this;
    }

    /**
     * Set the route names for controller actions.
	 * 设置控制器动作的路由名
     *
     * @param  array|string  $names
     * @return \Illuminate\Routing\PendingResourceRegistration
     */
    public function names($names)
    {
        $this->options['names'] = $names;

        return $this;
    }

    /**
     * Set the route name for a controller action.
	 * 设置控制器动作的路由名
     *
     * @param  string  $method
     * @param  string  $name
     * @return \Illuminate\Routing\PendingResourceRegistration
     */
    public function name($method, $name)
    {
        $this->options['names'][$method] = $name;

        return $this;
    }

    /**
     * Override the route parameter names.
	 * 覆盖路由参数名
     *
     * @param  array|string  $parameters
     * @return \Illuminate\Routing\PendingResourceRegistration
     */
    public function parameters($parameters)
    {
        $this->options['parameters'] = $parameters;

        return $this;
    }

    /**
     * Override a route parameter's name.
	 * 覆盖路由参数名称
     *
     * @param  string  $previous
     * @param  string  $new
     * @return \Illuminate\Routing\PendingResourceRegistration
     */
    public function parameter($previous, $new)
    {
        $this->options['parameters'][$previous] = $new;

        return $this;
    }

    /**
     * Add middleware to the resource routes.
	 * 添加中间件
     *
     * @param  mixed  $middleware
     * @return \Illuminate\Routing\PendingResourceRegistration
     */
    public function middleware($middleware)
    {
        $this->options['middleware'] = $middleware;

        return $this;
    }

    /**
     * Indicate that the resource routes should have "shallow" nesting.
	 * 指明资源路由应该有"浅"嵌套
     *
     * @param  bool  $shallow
     * @return \Illuminate\Routing\PendingResourceRegistration
     */
    public function shallow($shallow = true)
    {
        $this->options['shallow'] = $shallow;

        return $this;
    }

    /**
     * Register the resource route.
	 * 注册资源路由
     *
     * @return \Illuminate\Routing\RouteCollection
     */
    public function register()
    {
        $this->registered = true;

        return $this->registrar->register(
            $this->name, $this->controller, $this->options
        );
    }

    /**
     * Handle the object's destruction.
	 * 处理对象的销毁
     *
     * @return void
     */
    public function __destruct()
    {
        if (! $this->registered) {
            $this->register();
        }
    }
}

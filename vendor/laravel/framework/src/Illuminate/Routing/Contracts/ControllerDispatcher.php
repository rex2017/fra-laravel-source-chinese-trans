<?php
/**
 * 路由，契约控制器调度器
 */

namespace Illuminate\Routing\Contracts;

use Illuminate\Routing\Route;

interface ControllerDispatcher
{
    /**
     * Dispatch a request to a given controller and method.
	 * 将请求分派给给定的控制器和方法
     *
     * @param  \Illuminate\Routing\Route  $route
     * @param  mixed  $controller
     * @param  string  $method
     * @return mixed
     */
    public function dispatch(Route $route, $controller, $method);

    /**
     * Get the middleware for the controller instance.
	 * 得到控制器实例的中间件
     *
     * @param  \Illuminate\Routing\Controller  $controller
     * @param  string  $method
     * @return array
     */
    public function getMiddleware($controller, $method);
}

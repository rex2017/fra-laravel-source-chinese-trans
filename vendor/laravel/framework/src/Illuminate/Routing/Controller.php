<?php
/**
 * 路由控制器抽象类
 */

namespace Illuminate\Routing;

use BadMethodCallException;

abstract class Controller
{
    /**
     * The middleware registered on the controller.
	 * 注册在控制器上的中间件
     *
     * @var array
     */
    protected $middleware = [];

    /**
     * Register middleware on the controller.
	 * 注册中间件在控制器上
     *
     * @param  \Closure|array|string  $middleware
     * @param  array  $options
     * @return \Illuminate\Routing\ControllerMiddlewareOptions
     */
    public function middleware($middleware, array $options = [])
    {
        foreach ((array) $middleware as $m) {
            $this->middleware[] = [
                'middleware' => $m,
                'options' => &$options,
            ];
        }

        return new ControllerMiddlewareOptions($options);
    }

    /**
     * Get the middleware assigned to the controller.
	 * 得到分配给控制器的中间件
     *
     * @return array
     */
    public function getMiddleware()
    {
        return $this->middleware;
    }

    /**
     * Execute an action on the controller.
	 * 执行控制器的一个动作
     *
     * @param  string  $method
     * @param  array  $parameters
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function callAction($method, $parameters)
    {
        return $this->{$method}(...array_values($parameters));
    }

    /**
     * Handle calls to missing methods on the controller.
	 * 处理调用控制器上的缺失方法
     *
     * @param  string  $method
     * @param  array  $parameters
     * @return mixed
     *
     * @throws \BadMethodCallException
     */
    public function __call($method, $parameters)
    {
        throw new BadMethodCallException(sprintf(
            'Method %s::%s does not exist.', static::class, $method
        ));
    }
}

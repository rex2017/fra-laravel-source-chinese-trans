<?php
/**
 * 路由编译器
 */

namespace Illuminate\Routing;

use Symfony\Component\Routing\Route as SymfonyRoute;

class RouteCompiler
{
    /**
     * The route instance.
	 * 路由实例
     *
     * @var \Illuminate\Routing\Route
     */
    protected $route;

    /**
     * Create a new Route compiler instance.
	 * 创建新的路由编译实例
     *
     * @param  \Illuminate\Routing\Route  $route
     * @return void
     */
    public function __construct($route)
    {
        $this->route = $route;
    }

    /**
     * Compile the route.
	 * 编译路由
     *
     * @return \Symfony\Component\Routing\CompiledRoute
     */
    public function compile()
    {
        $optionals = $this->getOptionalParameters();

        $uri = preg_replace('/\{(\w+?)\?\}/', '{$1}', $this->route->uri());

        return (
            new SymfonyRoute($uri, $optionals, $this->route->wheres, ['utf8' => true], $this->route->getDomain() ?: '')
        )->compile();
    }

    /**
     * Get the optional parameters for the route.
	 * 得到路由的可选参数
     *
     * @return array
     */
    protected function getOptionalParameters()
    {
        preg_match_all('/\{(\w+?)\?\}/', $this->route->uri(), $matches);

        return isset($matches[1]) ? array_fill_keys($matches[1], null) : [];
    }
}

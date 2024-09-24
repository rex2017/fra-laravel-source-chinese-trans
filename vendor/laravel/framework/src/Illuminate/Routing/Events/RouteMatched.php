<?php
/**
 * 路由，事件路由匹配
 */

namespace Illuminate\Routing\Events;

class RouteMatched
{
    /**
     * The route instance.
	 * 路由实例
     *
     * @var \Illuminate\Routing\Route
     */
    public $route;

    /**
     * The request instance.
	 * 请求实例
     *
     * @var \Illuminate\Http\Request
     */
    public $request;

    /**
     * Create a new event instance.
	 * 创建新的事件实例
     *
     * @param  \Illuminate\Routing\Route  $route
     * @param  \Illuminate\Http\Request  $request
     * @return void
     */
    public function __construct($route, $request)
    {
        $this->route = $route;
        $this->request = $request;
    }
}

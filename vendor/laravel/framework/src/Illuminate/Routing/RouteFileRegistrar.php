<?php
/**
 * 路由文件注册器，即文件加载实现
 */

namespace Illuminate\Routing;

class RouteFileRegistrar
{
    /**
     * The router instance.
	 * 路由实例
     *
     * @var \Illuminate\Routing\Router
     */
    protected $router;

    /**
     * Create a new route file registrar instance.
	 * 创建新的路由文件注册实例
     *
     * @param  \Illuminate\Routing\Router  $router
     * @return void
     */
    public function __construct(Router $router)
    {
        $this->router = $router;
    }

    /**
     * Require the given routes file.
	 * 注册给定路由文件
     *
     * @param  string  $routes
     * @return void
     */
    public function register($routes)
    {
        $router = $this->router;

		//加载路由配置文件  先routes/api.php，再routes/web.php
        require $routes;
    }
}

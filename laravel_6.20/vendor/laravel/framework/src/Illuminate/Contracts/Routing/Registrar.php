<?php
/**
 * 契约，路由注册者接口
 */

namespace Illuminate\Contracts\Routing;

interface Registrar
{
    /**
     * Register a new GET route with the router.
	 * 注册一个新的get
     *
     * @param  string  $uri
     * @param  \Closure|array|string|callable  $action
     * @return \Illuminate\Routing\Route
     */
    public function get($uri, $action);

    /**
     * Register a new POST route with the router.
	 * 注册一个新的post
     *
     * @param  string  $uri
     * @param  \Closure|array|string|callable  $action
     * @return \Illuminate\Routing\Route
     */
    public function post($uri, $action);

    /**
     * Register a new PUT route with the router.
	 * 注册一个新的put
     *
     * @param  string  $uri
     * @param  \Closure|array|string|callable  $action
     * @return \Illuminate\Routing\Route
     */
    public function put($uri, $action);

    /**
     * Register a new DELETE route with the router.
	 * 注册一个新的delete
     *
     * @param  string  $uri
     * @param  \Closure|array|string|callable  $action
     * @return \Illuminate\Routing\Route
     */
    public function delete($uri, $action);

    /**
     * Register a new PATCH route with the router.
	 * 注册一个新的补丁
     *
     * @param  string  $uri
     * @param  \Closure|array|string|callable  $action
     * @return \Illuminate\Routing\Route
     */
    public function patch($uri, $action);

    /**
     * Register a new OPTIONS route with the router.
	 * 注册一个新的OPTIONS
     *
     * @param  string  $uri
     * @param  \Closure|array|string|callable  $action
     * @return \Illuminate\Routing\Route
     */
    public function options($uri, $action);

    /**
     * Register a new route with the given verbs.
     *
     * @param  array|string  $methods
     * @param  string  $uri
     * @param  \Closure|array|string|callable  $action
     * @return \Illuminate\Routing\Route
     */
    public function match($methods, $uri, $action);

    /**
     * Route a resource to a controller.
     *
     * @param  string  $name
     * @param  string  $controller
     * @param  array  $options
     * @return \Illuminate\Routing\PendingResourceRegistration
     */
    public function resource($name, $controller, array $options = []);

    /**
     * Create a route group with shared attributes.
     *
     * @param  array  $attributes
     * @param  \Closure|string  $routes
     * @return void
     */
    public function group(array $attributes, $routes);

    /**
     * Substitute the route bindings onto the route.
     *
     * @param  \Illuminate\Routing\Route  $route
     * @return \Illuminate\Routing\Route
     */
    public function substituteBindings($route);

    /**
     * Substitute the implicit Eloquent model bindings for the route.
     *
     * @param  \Illuminate\Routing\Route  $route
     * @return void
     */
    public function substituteImplicitBindings($route);
}

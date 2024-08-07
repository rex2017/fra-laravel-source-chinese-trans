<?php
/**
 * 路由，路由类，真正的实现方法类
 */

namespace Illuminate\Routing;

use ArrayObject;
use Closure;
use Illuminate\Container\Container;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Contracts\Routing\BindingRegistrar;
use Illuminate\Contracts\Routing\Registrar as RegistrarContract;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Contracts\Support\Jsonable;
use Illuminate\Contracts\Support\Responsable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Events\RouteMatched;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Illuminate\Support\Traits\Macroable;
use JsonSerializable;
use Psr\Http\Message\ResponseInterface as PsrResponseInterface;
use Symfony\Bridge\PsrHttpMessage\Factory\HttpFoundationFactory;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

/**
 * @mixin \Illuminate\Routing\RouteRegistrar
 */
class Router implements BindingRegistrar, RegistrarContract
{
    use Macroable {
        __call as macroCall;
    }

    /**
     * The event dispatcher instance.
	 * 事件调度实例
     *
     * @var \Illuminate\Contracts\Events\Dispatcher
     */
    protected $events;

    /**
     * The IoC container instance.
	 * 容器实例
     *
     * @var \Illuminate\Container\Container
     */
    protected $container;

    /**
     * The route collection instance.
	 * 路由集合实例
     *
     * @var \Illuminate\Routing\RouteCollection
     */
    protected $routes;

    /**
     * The currently dispatched route instance.
	 * 当前调度路由实例
     *
     * @var \Illuminate\Routing\Route|null
     */
    protected $current;

    /**
     * The request currently being dispatched.
	 * 当前被调度请求
     *
     * @var \Illuminate\Http\Request
     */
    protected $currentRequest;

    /**
     * All of the short-hand keys for middlewares.
	 * 所有中间件的快捷键
     * 
     * @var array
     */
    protected $middleware = [];

    /**
     * All of the middleware groups.
	 * 所有中间件分组
     *
     * @var array
     */
    protected $middlewareGroups = [];

    /**
     * The priority-sorted list of middleware.
	 * 中间件优先级排序列表
     *
     * Forces the listed middleware to always be in the given order.
     *
     * @var array
     */
    public $middlewarePriority = [];

    /**
     * The registered route value binders.
	 * 注册路由绑定
     *
     * @var array
     */
    protected $binders = [];

    /**
     * The globally available parameter patterns.
	 * 全局可用的参数模式
     *
     * @var array
     */
    protected $patterns = [];

    /**
     * The route group attribute stack.
	 * 路由组属性栈
     *
     * @var array
     */
    protected $groupStack = [];

    /**
     * All of the verbs supported by the router.
	 * 路由支持的所有动作
     *
     * @var array
     */
    public static $verbs = ['GET', 'HEAD', 'POST', 'PUT', 'PATCH', 'DELETE', 'OPTIONS'];

    /**
     * Create a new Router instance.
	 * 创建新的路由实例
     *
     * @param  \Illuminate\Contracts\Events\Dispatcher  $events
     * @param  \Illuminate\Container\Container|null  $container
     * @return void
     */
    public function __construct(Dispatcher $events, Container $container = null)
    {
        $this->events = $events;
        $this->routes = new RouteCollection;			#路由集合实例
        $this->container = $container ?: new Container;
    }

    /**
     * Register a new GET route with the router.
	 * 注册新的GET
     *
     * @param  string  $uri
     * @param  \Closure|array|string|callable|null  $action
     * @return \Illuminate\Routing\Route
     */
    public function get($uri, $action = null)
    {
        return $this->addRoute(['GET', 'HEAD'], $uri, $action);
    }

    /**
     * Register a new POST route with the router.
	 * 注册新的POST
     *
     * @param  string  $uri
     * @param  \Closure|array|string|callable|null  $action
     * @return \Illuminate\Routing\Route
     */
    public function post($uri, $action = null)
    {
        return $this->addRoute('POST', $uri, $action);
    }

    /**
     * Register a new PUT route with the router.
	 * 注册新的PUT
     *
     * @param  string  $uri
     * @param  \Closure|array|string|callable|null  $action
     * @return \Illuminate\Routing\Route
     */
    public function put($uri, $action = null)
    {
        return $this->addRoute('PUT', $uri, $action);
    }

    /**
     * Register a new PATCH route with the router.
	 * 注册新的补丁
     *
     * @param  string  $uri
     * @param  \Closure|array|string|callable|null  $action
     * @return \Illuminate\Routing\Route
     */
    public function patch($uri, $action = null)
    {
        return $this->addRoute('PATCH', $uri, $action);
    }

    /**
     * Register a new DELETE route with the router.
	 * 注册新的删除
     *
     * @param  string  $uri
     * @param  \Closure|array|string|callable|null  $action
     * @return \Illuminate\Routing\Route
     */
    public function delete($uri, $action = null)
    {
        return $this->addRoute('DELETE', $uri, $action);
    }

    /**
     * Register a new OPTIONS route with the router.
	 * 注册新的操作
     *
     * @param  string  $uri
     * @param  \Closure|array|string|callable|null  $action
     * @return \Illuminate\Routing\Route
     */
    public function options($uri, $action = null)
    {
        return $this->addRoute('OPTIONS', $uri, $action);
    }

    /**
     * Register a new route responding to all verbs.
	 * 注册新的路由响应所有动作
     *
     * @param  string  $uri
     * @param  \Closure|array|string|callable|null  $action
     * @return \Illuminate\Routing\Route
     */
    public function any($uri, $action = null)
    {
        return $this->addRoute(self::$verbs, $uri, $action);
    }

    /**
     * Register a new Fallback route with the router.
     *
     * @param  \Closure|array|string|callable|null  $action
     * @return \Illuminate\Routing\Route
     */
    public function fallback($action)
    {
        $placeholder = 'fallbackPlaceholder';

        return $this->addRoute(
            'GET', "{{$placeholder}}", $action
        )->where($placeholder, '.*')->fallback();
    }

    /**
     * Create a redirect from one URI to another.
	 * 创建重定向
     *
     * @param  string  $uri
     * @param  string  $destination
     * @param  int  $status
     * @return \Illuminate\Routing\Route
     */
    public function redirect($uri, $destination, $status = 302)
    {
        return $this->any($uri, '\Illuminate\Routing\RedirectController')
                ->defaults('destination', $destination)
                ->defaults('status', $status);
    }

    /**
     * Create a permanent redirect from one URI to another.
     *
     * @param  string  $uri
     * @param  string  $destination
     * @return \Illuminate\Routing\Route
     */
    public function permanentRedirect($uri, $destination)
    {
        return $this->redirect($uri, $destination, 301);
    }

    /**
     * Register a new route that returns a view.
	 * 注册新的路由
     *
     * @param  string  $uri
     * @param  string  $view
     * @param  array  $data
     * @return \Illuminate\Routing\Route
     */
    public function view($uri, $view, $data = [])
    {
        return $this->match(['GET', 'HEAD'], $uri, '\Illuminate\Routing\ViewController')
                ->defaults('view', $view)
                ->defaults('data', $data);
    }

    /**
     * Register a new route with the given verbs.
	 * 注册新的路由
     *
     * @param  array|string  $methods
     * @param  string  $uri
     * @param  \Closure|array|string|callable|null  $action
     * @return \Illuminate\Routing\Route
     */
    public function match($methods, $uri, $action = null)
    {
        return $this->addRoute(array_map('strtoupper', (array) $methods), $uri, $action);
    }

    /**
     * Register an array of resource controllers.
	 * 注册控制器资源
     *
     * @param  array  $resources
     * @param  array  $options
     * @return void
     */
    public function resources(array $resources, array $options = [])
    {
        foreach ($resources as $name => $controller) {
            $this->resource($name, $controller, $options);
        }
    }

    /**
     * Route a resource to a controller.
	 * 路由控制器资源
     *
     * @param  string  $name
     * @param  string  $controller
     * @param  array  $options
     * @return \Illuminate\Routing\PendingResourceRegistration
     */
    public function resource($name, $controller, array $options = [])
    {
        if ($this->container && $this->container->bound(ResourceRegistrar::class)) {
            $registrar = $this->container->make(ResourceRegistrar::class);
        } else {
            $registrar = new ResourceRegistrar($this);
        }

        return new PendingResourceRegistration(
            $registrar, $name, $controller, $options
        );
    }

    /**
     * Register an array of API resource controllers.
	 * 注册API资源
     *
     * @param  array  $resources
     * @param  array  $options
     * @return void
     */
    public function apiResources(array $resources, array $options = [])
    {
        foreach ($resources as $name => $controller) {
            $this->apiResource($name, $controller, $options);
        }
    }

    /**
     * Route an API resource to a controller.
	 * 路由到API资源
     *
     * @param  string  $name
     * @param  string  $controller
     * @param  array  $options
     * @return \Illuminate\Routing\PendingResourceRegistration
     */
    public function apiResource($name, $controller, array $options = [])
    {
        $only = ['index', 'show', 'store', 'update', 'destroy'];

        if (isset($options['except'])) {
            $only = array_diff($only, (array) $options['except']);
        }

        return $this->resource($name, $controller, array_merge([
            'only' => $only,
        ], $options));
    }

    /**
     * Create a route group with shared attributes.
     *
     * @param  array  $attributes
     * @param  \Closure|string  $routes
     * @return void
     */
    public function group(array $attributes, $routes)
    {
        $this->updateGroupStack($attributes);

        // Once we have updated the group stack, we'll load the provided routes and
        // merge in the group's attributes when the routes are created. After we
        // have created the routes, we will pop the attributes off the stack.
        $this->loadRoutes($routes);

        array_pop($this->groupStack);
    }

    /**
     * Update the group stack with the given attributes.
     *
     * @param  array  $attributes
     * @return void
     */
    protected function updateGroupStack(array $attributes)
    {
        if ($this->hasGroupStack()) {
            $attributes = $this->mergeWithLastGroup($attributes);
        }

        $this->groupStack[] = $attributes;
    }

    /**
     * Merge the given array with the last group stack.
     *
     * @param  array  $new
     * @return array
     */
    public function mergeWithLastGroup($new)
    {
        return RouteGroup::merge($new, end($this->groupStack));
    }

    /**
     * Load the provided routes.
	 * 加载提供的路由
     *
     * @param  \Closure|string  $routes
     * @return void
     */
    protected function loadRoutes($routes)
    {
        if ($routes instanceof Closure) {
            $routes($this);
        } else {
            (new RouteFileRegistrar($this))->register($routes);
        }
    }

    /**
     * Get the prefix from the last group on the stack.
	 * 得到前缀从堆栈上的最后一个组
     *
     * @return string
     */
    public function getLastGroupPrefix()
    {
        if ($this->hasGroupStack()) {
            $last = end($this->groupStack);

            return $last['prefix'] ?? '';
        }

        return '';
    }

    /**
     * Add a route to the underlying route collection.
	 * 添加路由至底层路由集合
     *
     * @param  array|string  $methods
     * @param  string  $uri
     * @param  \Closure|array|string|callable|null  $action
     * @return \Illuminate\Routing\Route
     */
    public function addRoute($methods, $uri, $action)
    {
        return $this->routes->add($this->createRoute($methods, $uri, $action));
    }

    /**
     * Create a new route instance.
	 * 创建新的路由实例
     *
     * @param  array|string  $methods
     * @param  string  $uri
     * @param  mixed  $action
     * @return \Illuminate\Routing\Route
     */
    protected function createRoute($methods, $uri, $action)
    {
        // If the route is routing to a controller we will parse the route action into
        // an acceptable array format before registering it and creating this route
        // instance itself. We need to build the Closure that will call this out.
		// 如果路由是路由到控制器，我们将解析路由动作至一个可接受的数组并创建路由实例本身。
        if ($this->actionReferencesController($action)) {
            $action = $this->convertToControllerAction($action);
        }

        $route = $this->newRoute(
            $methods, $this->prefix($uri), $action
        );

        // If we have groups that need to be merged, we will merge them now after this
        // route has already been created and is ready to go. After we're done with
        // the merge we will be ready to return the route back out to the caller.
		// 如果我们有需要合并的组，我们将在此之后立即合并它们.
        if ($this->hasGroupStack()) {
            $this->mergeGroupAttributesIntoRoute($route);
        }

        $this->addWhereClausesToRoute($route);

        return $route;
    }

    /**
     * Determine if the action is routing to a controller.
     *
     * @param  array  $action
     * @return bool
     */
    protected function actionReferencesController($action)
    {
        if (! $action instanceof Closure) {
            return is_string($action) || (isset($action['uses']) && is_string($action['uses']));
        }

        return false;
    }

    /**
     * Add a controller based route action to the action array.
     *
     * @param  array|string  $action
     * @return array
     */
    protected function convertToControllerAction($action)
    {
        if (is_string($action)) {
            $action = ['uses' => $action];
        }

        // Here we'll merge any group "uses" statement if necessary so that the action
        // has the proper clause for this property. Then we can simply set the name
        // of the controller on the action and return the action array for usage.
        if ($this->hasGroupStack()) {
            $action['uses'] = $this->prependGroupNamespace($action['uses']);
        }

        // Here we will set this controller name on the action array just so we always
        // have a copy of it for reference if we need it. This can be used while we
        // search for a controller name or do some other type of fetch operation.
        $action['controller'] = $action['uses'];

        return $action;
    }

    /**
     * Prepend the last group namespace onto the use clause.
     *
     * @param  string  $class
     * @return string
     */
    protected function prependGroupNamespace($class)
    {
        $group = end($this->groupStack);

        return isset($group['namespace']) && strpos($class, '\\') !== 0
                ? $group['namespace'].'\\'.$class : $class;
    }

    /**
     * Create a new Route object.
     *
     * @param  array|string  $methods
     * @param  string  $uri
     * @param  mixed  $action
     * @return \Illuminate\Routing\Route
     */
    protected function newRoute($methods, $uri, $action)
    {
        return (new Route($methods, $uri, $action))
                    ->setRouter($this)
                    ->setContainer($this->container);
    }

    /**
     * Prefix the given URI with the last prefix.
     *
     * @param  string  $uri
     * @return string
     */
    protected function prefix($uri)
    {
        return trim(trim($this->getLastGroupPrefix(), '/').'/'.trim($uri, '/'), '/') ?: '/';
    }

    /**
     * Add the necessary where clauses to the route based on its initial registration.
     *
     * @param  \Illuminate\Routing\Route  $route
     * @return \Illuminate\Routing\Route
     */
    protected function addWhereClausesToRoute($route)
    {
        $route->where(array_merge(
            $this->patterns, $route->getAction()['where'] ?? []
        ));

        return $route;
    }

    /**
     * Merge the group stack with the controller action.
     *
     * @param  \Illuminate\Routing\Route  $route
     * @return void
     */
    protected function mergeGroupAttributesIntoRoute($route)
    {
        $route->setAction($this->mergeWithLastGroup($route->getAction()));
    }

    /**
     * Return the response returned by the given route.
     *
     * @param  string  $name
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function respondWithRoute($name)
    {
        $route = tap($this->routes->getByName($name))->bind($this->currentRequest);

        return $this->runRoute($this->currentRequest, $route);
    }

    /**
     * Dispatch the request to the application.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function dispatch(Request $request)
    {
        $this->currentRequest = $request;

        return $this->dispatchToRoute($request);
    }

    /**
     * Dispatch the request to a route and return the response.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function dispatchToRoute(Request $request)
    {
        return $this->runRoute($request, $this->findRoute($request));
    }

    /**
     * Find the route matching a given request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Routing\Route
     */
    protected function findRoute($request)
    {
        $this->current = $route = $this->routes->match($request);

        $this->container->instance(Route::class, $route);

        return $route;
    }

    /**
     * Return the response for the given route.
	 * 返回路由响应
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Illuminate\Routing\Route  $route
     * @return \Symfony\Component\HttpFoundation\Response
     */
    protected function runRoute(Request $request, Route $route)
    {
        $request->setRouteResolver(function () use ($route) {
            return $route;
        });

        $this->events->dispatch(new RouteMatched($route, $request));

        return $this->prepareResponse($request,
            $this->runRouteWithinStack($route, $request)
        );
    }

    /**
     * Run the given route within a Stack "onion" instance.
	 * 运行给定路由在堆栈中
     *
     * @param  \Illuminate\Routing\Route  $route
     * @param  \Illuminate\Http\Request  $request
     * @return mixed
     */
    protected function runRouteWithinStack(Route $route, Request $request)
    {
        $shouldSkipMiddleware = $this->container->bound('middleware.disable') &&
                                $this->container->make('middleware.disable') === true;

        $middleware = $shouldSkipMiddleware ? [] : $this->gatherRouteMiddleware($route);
		/**
		 * array:6 [▼
		 *	0 => "App\Http\Middleware\EncryptCookies"
		 *	1 => "Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse"
		 *	2 => "Illuminate\Session\Middleware\StartSession"
		 *	3 => "Illuminate\View\Middleware\ShareErrorsFromSession"
		 *	4 => "App\Http\Middleware\VerifyCsrfToken"
		 *	5 => "Illuminate\Routing\Middleware\SubstituteBindings"
		 *	]
		 */

        return (new Pipeline($this->container))
                        ->send($request)
                        ->through($middleware)
                        ->then(function ($request) use ($route) {
                            return $this->prepareResponse(
                                $request, $route->run()				#此处调用run方法
                            );
                        });
    }

    /**
     * Gather the middleware for the given route with resolved class names.
	 * 收集具有解析类名的给定路由的中间件
     *
     * @param  \Illuminate\Routing\Route  $route
     * @return array
     */
    public function gatherRouteMiddleware(Route $route)
    {
        $middleware = collect($route->gatherMiddleware())->map(function ($name) {
            return (array) MiddlewareNameResolver::resolve($name, $this->middleware, $this->middlewareGroups);
        })->flatten();

        return $this->sortMiddleware($middleware);
    }

    /**
     * Sort the given middleware by priority.
     *
     * @param  \Illuminate\Support\Collection  $middlewares
     * @return array
     */
    protected function sortMiddleware(Collection $middlewares)
    {
        return (new SortedMiddleware($this->middlewarePriority, $middlewares))->all();
    }

    /**
     * Create a response instance from the given value.
     *
     * @param  \Symfony\Component\HttpFoundation\Request  $request
     * @param  mixed  $response
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function prepareResponse($request, $response)
    {
        return static::toResponse($request, $response);
    }

    /**
     * Static version of prepareResponse.
     *
     * @param  \Symfony\Component\HttpFoundation\Request  $request
     * @param  mixed  $response
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public static function toResponse($request, $response)
    {
        if ($response instanceof Responsable) {
            $response = $response->toResponse($request);
        }

        if ($response instanceof PsrResponseInterface) {
            $response = (new HttpFoundationFactory)->createResponse($response);
        } elseif ($response instanceof Model && $response->wasRecentlyCreated) {
            $response = new JsonResponse($response, 201);
        } elseif (! $response instanceof SymfonyResponse &&
                   ($response instanceof Arrayable ||
                    $response instanceof Jsonable ||
                    $response instanceof ArrayObject ||
                    $response instanceof JsonSerializable ||
                    is_array($response))) {
            $response = new JsonResponse($response);
        } elseif (! $response instanceof SymfonyResponse) {
            $response = new Response($response, 200, ['Content-Type' => 'text/html']);
        }

        if ($response->getStatusCode() === Response::HTTP_NOT_MODIFIED) {
            $response->setNotModified();
        }

        return $response->prepare($request);
    }

    /**
     * Substitute the route bindings onto the route.
     *
     * @param  \Illuminate\Routing\Route  $route
     * @return \Illuminate\Routing\Route
     *
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     */
    public function substituteBindings($route)
    {
        foreach ($route->parameters() as $key => $value) {
            if (isset($this->binders[$key])) {
                $route->setParameter($key, $this->performBinding($key, $value, $route));
            }
        }

        return $route;
    }

    /**
     * Substitute the implicit Eloquent model bindings for the route.
     *
     * @param  \Illuminate\Routing\Route  $route
     * @return void
     *
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     */
    public function substituteImplicitBindings($route)
    {
        ImplicitRouteBinding::resolveForRoute($this->container, $route);
    }

    /**
     * Call the binding callback for the given key.
     *
     * @param  string  $key
     * @param  string  $value
     * @param  \Illuminate\Routing\Route  $route
     * @return mixed
     *
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     */
    protected function performBinding($key, $value, $route)
    {
        return call_user_func($this->binders[$key], $value, $route);
    }

    /**
     * Register a route matched event listener.
     *
     * @param  string|callable  $callback
     * @return void
     */
    public function matched($callback)
    {
        $this->events->listen(Events\RouteMatched::class, $callback);
    }

    /**
     * Get all of the defined middleware short-hand names.
	 * 得到所有已定义的中间件简写名称
     *
     * @return array
     */
    public function getMiddleware()
    {
        return $this->middleware;
    }

    /**
     * Register a short-hand name for a middleware.
     *
     * @param  string  $name
     * @param  string  $class
     * @return $this
     */
    public function aliasMiddleware($name, $class)
    {
        $this->middleware[$name] = $class;

        return $this;
    }

    /**
     * Check if a middlewareGroup with the given name exists.
     *
     * @param  string  $name
     * @return bool
     */
    public function hasMiddlewareGroup($name)
    {
        return array_key_exists($name, $this->middlewareGroups);
    }

    /**
     * Get all of the defined middleware groups.
     *
     * @return array
     */
    public function getMiddlewareGroups()
    {
        return $this->middlewareGroups;
    }

    /**
     * Register a group of middleware.
     *
     * @param  string  $name
     * @param  array  $middleware
     * @return $this
     */
    public function middlewareGroup($name, array $middleware)
    {
        $this->middlewareGroups[$name] = $middleware;

        return $this;
    }

    /**
     * Add a middleware to the beginning of a middleware group.
     *
     * If the middleware is already in the group, it will not be added again.
     *
     * @param  string  $group
     * @param  string  $middleware
     * @return $this
     */
    public function prependMiddlewareToGroup($group, $middleware)
    {
        if (isset($this->middlewareGroups[$group]) && ! in_array($middleware, $this->middlewareGroups[$group])) {
            array_unshift($this->middlewareGroups[$group], $middleware);
        }

        return $this;
    }

    /**
     * Add a middleware to the end of a middleware group.
     *
     * If the middleware is already in the group, it will not be added again.
     *
     * @param  string  $group
     * @param  string  $middleware
     * @return $this
     */
    public function pushMiddlewareToGroup($group, $middleware)
    {
        if (! array_key_exists($group, $this->middlewareGroups)) {
            $this->middlewareGroups[$group] = [];
        }

        if (! in_array($middleware, $this->middlewareGroups[$group])) {
            $this->middlewareGroups[$group][] = $middleware;
        }

        return $this;
    }

    /**
     * Add a new route parameter binder.
	 * 添加一个新的路由参数绑定器
     *
     * @param  string  $key
     * @param  string|callable  $binder
     * @return void
     */
    public function bind($key, $binder)
    {
        $this->binders[str_replace('-', '_', $key)] = RouteBinding::forCallback(
            $this->container, $binder
        );
    }

    /**
     * Register a model binder for a wildcard.
	 * 注册一个模型绑定器为通配符
     *
     * @param  string  $key
     * @param  string  $class
     * @param  \Closure|null  $callback
     * @return void
     */
    public function model($key, $class, Closure $callback = null)
    {
        $this->bind($key, RouteBinding::forModel($this->container, $class, $callback));
    }

    /**
     * Get the binding callback for a given binding.
     *
     * @param  string  $key
     * @return \Closure|null
     */
    public function getBindingCallback($key)
    {
        if (isset($this->binders[$key = str_replace('-', '_', $key)])) {
            return $this->binders[$key];
        }
    }

    /**
     * Get the global "where" patterns.
     *
     * @return array
     */
    public function getPatterns()
    {
        return $this->patterns;
    }

    /**
     * Set a global where pattern on all routes.
     *
     * @param  string  $key
     * @param  string  $pattern
     * @return void
     */
    public function pattern($key, $pattern)
    {
        $this->patterns[$key] = $pattern;
    }

    /**
     * Set a group of global where patterns on all routes.
     *
     * @param  array  $patterns
     * @return void
     */
    public function patterns($patterns)
    {
        foreach ($patterns as $key => $pattern) {
            $this->pattern($key, $pattern);
        }
    }

    /**
     * Determine if the router currently has a group stack.
     *
     * @return bool
     */
    public function hasGroupStack()
    {
        return ! empty($this->groupStack);
    }

    /**
     * Get the current group stack for the router.
     *
     * @return array
     */
    public function getGroupStack()
    {
        return $this->groupStack;
    }

    /**
     * Get a route parameter for the current route.
     *
     * @param  string  $key
     * @param  string|null  $default
     * @return mixed
     */
    public function input($key, $default = null)
    {
        return $this->current()->parameter($key, $default);
    }

    /**
     * Get the request currently being dispatched.
     *
     * @return \Illuminate\Http\Request
     */
    public function getCurrentRequest()
    {
        return $this->currentRequest;
    }

    /**
     * Get the currently dispatched route instance.
	 * 得到当前调度的路由实例
     *
     * @return \Illuminate\Routing\Route
     */
    public function getCurrentRoute()
    {
        return $this->current();
    }

    /**
     * Get the currently dispatched route instance.
     *
     * @return \Illuminate\Routing\Route|null
     */
    public function current()
    {
        return $this->current;
    }

    /**
     * Check if a route with the given name exists.
     *
     * @param  string  $name
     * @return bool
     */
    public function has($name)
    {
        $names = is_array($name) ? $name : func_get_args();

        foreach ($names as $value) {
            if (! $this->routes->hasNamedRoute($value)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Get the current route name.
	 * 得到当前路由名
     *
     * @return string|null
     */
    public function currentRouteName()
    {
        return $this->current() ? $this->current()->getName() : null;
    }

    /**
     * Alias for the "currentRouteNamed" method.
     *
     * @param  mixed  ...$patterns
     * @return bool
     */
    public function is(...$patterns)
    {
        return $this->currentRouteNamed(...$patterns);
    }

    /**
     * Determine if the current route matches a pattern.
	 * 确定当前路由是否与模式匹配
     *
     * @param  mixed  ...$patterns
     * @return bool
     */
    public function currentRouteNamed(...$patterns)
    {
        return $this->current() && $this->current()->named(...$patterns);
    }

    /**
     * Get the current route action.
	 * 得到当前路由动作
     *
     * @return string|null
     */
    public function currentRouteAction()
    {
        if ($this->current()) {
            return $this->current()->getAction()['controller'] ?? null;
        }
    }

    /**
     * Alias for the "currentRouteUses" method.
     *
     * @param  array  ...$patterns
     * @return bool
     */
    public function uses(...$patterns)
    {
        foreach ($patterns as $pattern) {
            if (Str::is($pattern, $this->currentRouteAction())) {
                return true;
            }
        }

        return false;
    }

    /**
     * Determine if the current route action matches a given action.
	 * 确定当前路由操作是否与给定操作匹配
     *
     * @param  string  $action
     * @return bool
     */
    public function currentRouteUses($action)
    {
        return $this->currentRouteAction() == $action;
    }

    /**
     * Register the typical authentication routes for an application.
     *
     * @param  array  $options
     * @return void
     */
    public function auth(array $options = [])
    {
        // Authentication Routes...
        $this->get('login', 'Auth\LoginController@showLoginForm')->name('login');
        $this->post('login', 'Auth\LoginController@login');
        $this->post('logout', 'Auth\LoginController@logout')->name('logout');

        // Registration Routes...
        if ($options['register'] ?? true) {
            $this->get('register', 'Auth\RegisterController@showRegistrationForm')->name('register');
            $this->post('register', 'Auth\RegisterController@register');
        }

        // Password Reset Routes...
        if ($options['reset'] ?? true) {
            $this->resetPassword();
        }

        // Password Confirmation Routes...
        if ($options['confirm'] ??
            class_exists($this->prependGroupNamespace('Auth\ConfirmPasswordController'))) {
            $this->confirmPassword();
        }

        // Email Verification Routes...
        if ($options['verify'] ?? false) {
            $this->emailVerification();
        }
    }

    /**
     * Register the typical reset password routes for an application.
     *
     * @return void
     */
    public function resetPassword()
    {
        $this->get('password/reset', 'Auth\ForgotPasswordController@showLinkRequestForm')->name('password.request');
        $this->post('password/email', 'Auth\ForgotPasswordController@sendResetLinkEmail')->name('password.email');
        $this->get('password/reset/{token}', 'Auth\ResetPasswordController@showResetForm')->name('password.reset');
        $this->post('password/reset', 'Auth\ResetPasswordController@reset')->name('password.update');
    }

    /**
     * Register the typical confirm password routes for an application.
	 * 为应用程序注册典型的确认密码路由
     *
     * @return void
     */
    public function confirmPassword()
    {
        $this->get('password/confirm', 'Auth\ConfirmPasswordController@showConfirmForm')->name('password.confirm');
        $this->post('password/confirm', 'Auth\ConfirmPasswordController@confirm');
    }

    /**
     * Register the typical email verification routes for an application.
	 * 为应用程序注册典型的电子邮件验证路由
     *
     * @return void
     */
    public function emailVerification()
    {
        $this->get('email/verify', 'Auth\VerificationController@show')->name('verification.notice');
        $this->get('email/verify/{id}/{hash}', 'Auth\VerificationController@verify')->name('verification.verify');
        $this->post('email/resend', 'Auth\VerificationController@resend')->name('verification.resend');
    }

    /**
     * Set the unmapped global resource parameters to singular.
     *
     * @param  bool  $singular
     * @return void
     */
    public function singularResourceParameters($singular = true)
    {
        ResourceRegistrar::singularParameters($singular);
    }

    /**
     * Set the global resource parameter mapping.
	 * 设置全局资源参数映射
     *
     * @param  array  $parameters
     * @return void
     */
    public function resourceParameters(array $parameters = [])
    {
        ResourceRegistrar::setParameters($parameters);
    }

    /**
     * Get or set the verbs used in the resource URIs.
     *
     * @param  array  $verbs
     * @return array|null
     */
    public function resourceVerbs(array $verbs = [])
    {
        return ResourceRegistrar::verbs($verbs);
    }

    /**
     * Get the underlying route collection.
	 * 获取底层路由集合
     *
     * @return \Illuminate\Routing\RouteCollection
     */
    public function getRoutes()
    {
        return $this->routes;
    }

    /**
     * Set the route collection instance.
	 * 设置路由集合实例
     *
     * @param  \Illuminate\Routing\RouteCollection  $routes
     * @return void
     */
    public function setRoutes(RouteCollection $routes)
    {
        foreach ($routes as $route) {
            $route->setRouter($this)->setContainer($this->container);
        }

        $this->routes = $routes;

        $this->container->instance('routes', $this->routes);
    }

    /**
     * Remove any duplicate middleware from the given array.
	 * 删除任何重复的中间件从给定数组中
     *
     * @param  array  $middleware
     * @return array
     */
    public static function uniqueMiddleware(array $middleware)
    {
        $seen = [];
        $result = [];

        foreach ($middleware as $value) {
            $key = \is_object($value) ? \spl_object_id($value) : $value;

            if (! isset($seen[$key])) {
                $seen[$key] = true;
                $result[] = $value;
            }
        }

        return $result;
    }

    /**
     * Dynamically handle calls into the router instance.
	 * 动态调取处理程序
     *
     * @param  string  $method
     * @param  array  $parameters
     * @return mixed
     */
    public function __call($method, $parameters)
    {
        if (static::hasMacro($method)) {
            return $this->macroCall($method, $parameters);
        }

        if ($method === 'middleware') {
            return (new RouteRegistrar($this))->attribute($method, is_array($parameters[0]) ? $parameters[0] : $parameters);
        }

        return (new RouteRegistrar($this))->attribute($method, $parameters[0]);
    }
}

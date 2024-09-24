<?php
/**
 * 路由注册器
 */

namespace Illuminate\Routing;

use BadMethodCallException;
use Closure;
use Illuminate\Support\Arr;
use Illuminate\Support\Reflector;
use InvalidArgumentException;

/**
 * @method \Illuminate\Routing\Route get(string $uri, \Closure|array|string|null $action = null)
 * @method \Illuminate\Routing\Route post(string $uri, \Closure|array|string|null $action = null)
 * @method \Illuminate\Routing\Route put(string $uri, \Closure|array|string|null $action = null)
 * @method \Illuminate\Routing\Route delete(string $uri, \Closure|array|string|null $action = null)
 * @method \Illuminate\Routing\Route patch(string $uri, \Closure|array|string|null $action = null)
 * @method \Illuminate\Routing\Route options(string $uri, \Closure|array|string|null $action = null)
 * @method \Illuminate\Routing\Route any(string $uri, \Closure|array|string|null $action = null)
 * @method \Illuminate\Routing\RouteRegistrar as(string $value)
 * @method \Illuminate\Routing\RouteRegistrar domain(string $value)
 * @method \Illuminate\Routing\RouteRegistrar middleware(array|string|null $middleware)
 * @method \Illuminate\Routing\RouteRegistrar name(string $value)
 * @method \Illuminate\Routing\RouteRegistrar namespace(string $value)
 * @method \Illuminate\Routing\RouteRegistrar prefix(string  $prefix)
 * @method \Illuminate\Routing\RouteRegistrar where(array  $where)
 */
class RouteRegistrar
{
    /**
     * The router instance.
	 * 路由实例
     *
     * @var \Illuminate\Routing\Router
     */
    protected $router;

    /**
     * The attributes to pass on to the router.
	 * 路由属性
     *
     * @var array
     */
    protected $attributes = [];

    /**
     * The methods to dynamically pass through to the router.
	 * 动态传递给路由的方法
     *
     * @var array
     */
    protected $passthru = [
        'get', 'post', 'put', 'patch', 'delete', 'options', 'any',
    ];

    /**
     * The attributes that can be set through this class.
	 * 允许属性
     *
     * @var array
     */
    protected $allowedAttributes = [
        'as', 'domain', 'middleware', 'name', 'namespace', 'prefix', 'where',
    ];

    /**
     * The attributes that are aliased.
	 * 属性别名
     *
     * @var array
     */
    protected $aliases = [
        'name' => 'as',
    ];

    /**
     * Create a new route registrar instance.
	 * 创建新的路由注册实例
     *
     * @param  \Illuminate\Routing\Router  $router
     * @return void
     */
    public function __construct(Router $router)
    {
        $this->router = $router;
    }

    /**
     * Set the value for a given attribute.
	 * 设置给定属性值
     *
     * @param  string  $key
     * @param  mixed  $value
     * @return $this
     *
     * @throws \InvalidArgumentException
     */
    public function attribute($key, $value)
    {
        if (! in_array($key, $this->allowedAttributes)) {
            throw new InvalidArgumentException("Attribute [{$key}] does not exist.");
        }

        $this->attributes[Arr::get($this->aliases, $key, $key)] = $value;

        return $this;
    }

    /**
     * Route a resource to a controller.
	 * 将资源路由到控制器
     *
     * @param  string  $name
     * @param  string  $controller
     * @param  array  $options
     * @return \Illuminate\Routing\PendingResourceRegistration
     */
    public function resource($name, $controller, array $options = [])
    {
        return $this->router->resource($name, $controller, $this->attributes + $options);
    }

    /**
     * Create a route group with shared attributes.
	 * 创建具有共享属性的路由组
     *
     * @param  \Closure|string  $callback
     * @return void
     */
    public function group($callback)
    {
        $this->router->group($this->attributes, $callback);
    }

    /**
     * Register a new route with the given verbs.
	 * 注册一条新路线用给定的动词
     *
     * @param  array|string  $methods
     * @param  string  $uri
     * @param  \Closure|array|string|null  $action
     * @return \Illuminate\Routing\Route
     */
    public function match($methods, $uri, $action = null)
    {
        return $this->router->match($methods, $uri, $this->compileAction($action));
    }

    /**
     * Register a new route with the router.
	 * 注册一条新路线使用路由
     *
     * @param  string  $method
     * @param  string  $uri
     * @param  \Closure|array|string|null  $action
     * @return \Illuminate\Routing\Route
     */
    protected function registerRoute($method, $uri, $action = null)
    {
        if (! is_array($action)) {
            $action = array_merge($this->attributes, $action ? ['uses' => $action] : []);
        }

        return $this->router->{$method}($uri, $this->compileAction($action));
    }

    /**
     * Compile the action into an array including the attributes.
	 * 编译动作成包含属性的数组
     *
     * @param  \Closure|array|string|null  $action
     * @return array
     */
    protected function compileAction($action)
    {
        if (is_null($action)) {
            return $this->attributes;
        }

        if (is_string($action) || $action instanceof Closure) {
            $action = ['uses' => $action];
        }

        if (is_array($action) &&
            ! Arr::isAssoc($action) &&
            Reflector::isCallable($action)) {
            $action = [
                'uses' => $action[0].'@'.$action[1],
                'controller' => $action[0].'@'.$action[1],
            ];
        }

        return array_merge($this->attributes, $action);
    }

    /**
     * Dynamically handle calls into the route registrar.
	 * 动态调取方法
     *
     * @param  string  $method
     * @param  array  $parameters
     * @return \Illuminate\Routing\Route|$this
     *
     * @throws \BadMethodCallException
     */
    public function __call($method, $parameters)
    {
        if (in_array($method, $this->passthru)) {
            return $this->registerRoute($method, ...$parameters);
        }

        if (in_array($method, $this->allowedAttributes)) {
            if ($method === 'middleware') {
                return $this->attribute($method, is_array($parameters[0]) ? $parameters[0] : $parameters);
            }

            return $this->attribute($method, $parameters[0]);
        }

        throw new BadMethodCallException(sprintf(
            'Method %s::%s does not exist.', static::class, $method
        ));
    }
}

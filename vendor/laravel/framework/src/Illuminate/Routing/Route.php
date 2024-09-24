<?php
/**
 * 路由类，提供给路由配置文件里使用，类似于门面
 */

namespace Illuminate\Routing;

use Closure;
use Illuminate\Container\Container;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\Request;
use Illuminate\Routing\Contracts\ControllerDispatcher as ControllerDispatcherContract;
use Illuminate\Routing\Matching\HostValidator;
use Illuminate\Routing\Matching\MethodValidator;
use Illuminate\Routing\Matching\SchemeValidator;
use Illuminate\Routing\Matching\UriValidator;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Illuminate\Support\Traits\Macroable;
use LogicException;
use ReflectionFunction;

class Route
{
    use Macroable, RouteDependencyResolverTrait;

    /**
     * The URI pattern the route responds to.
	 * URI模式
	 * URI
     *
     * @var string
     */
    public $uri;

    /**
     * The HTTP methods the route responds to.
	 * HTTP方法
     *
     * @var array
     */
    public $methods;

    /**
     * The route action array.
	 * 路由动作
     *
     * @var array
     */
    public $action;

    /**
     * Indicates whether the route is a fallback route.
	 * 指明是否为回退路由
     *
     * @var bool
     */
    public $isFallback = false;

    /**
     * The controller instance.
	 * 控制器实例
     *
     * @var mixed
     */
    public $controller;

    /**
     * The default values for the route.
	 * 默认值
     *
     * @var array
     */
    public $defaults = [];

    /**
     * The regular expression requirements.
	 * 正则表达式要求
     *
     * @var array
     */
    public $wheres = [];

    /**
     * The array of matched parameters.
	 * 匹配参数的数组
     *
     * @var array|null
     */
    public $parameters;

    /**
     * The parameter names for the route.
	 * 路由参数
     *
     * @var array|null
     */
    public $parameterNames;

    /**
     * The array of the matched parameters' original values.
	 * 匹配参数的原始值
     *
     * @var array
     */
    protected $originalParameters;

    /**
     * The computed gathered middleware.
	 * 计算集合中间件
     *
     * @var array|null
     */
    public $computedMiddleware;

    /**
     * The compiled version of the route.
	 * 编译版本
     *
     * @var \Symfony\Component\Routing\CompiledRoute
     */
    public $compiled;

    /**
     * The router instance used by the route.
	 * 路由实例
     *
     * @var \Illuminate\Routing\Router
     */
    protected $router;

    /**
     * The container instance used by the route.
	 * 容器实例
     *
     * @var \Illuminate\Container\Container
     */
    protected $container;

    /**
     * The validators used by the routes.
	 * 验证器
     *
     * @var array
     */
    public static $validators;

    /**
     * Create a new Route instance.
	 * 创建新的路由实例
     *
     * @param  array|string  $methods
     * @param  string  $uri
     * @param  \Closure|array  $action
     * @return void
     */
    public function __construct($methods, $uri, $action)
    {
        $this->uri = $uri;
        $this->methods = (array) $methods;
        $this->action = $this->parseAction($action);

        if (in_array('GET', $this->methods) && ! in_array('HEAD', $this->methods)) {
            $this->methods[] = 'HEAD';
        }

        if (isset($this->action['prefix'])) {
            $this->prefix($this->action['prefix']);
        }
    }

    /**
     * Parse the route action into a standard array.
	 * 解析路由动作至标准数组
     *
     * @param  callable|array|null  $action
     * @return array
     *
     * @throws \UnexpectedValueException
     */
    protected function parseAction($action)
    {
        return RouteAction::parse($this->uri, $action);
    }

    /**
     * Run the route action and return the response.
	 * 运行路由动作返回响应
     *
     * @return mixed
     */
    public function run()
    {
        $this->container = $this->container ?: new Container;

        try {
            if ($this->isControllerAction()) {
				//真正的执行控制器
                return $this->runController();
            }

            return $this->runCallable();
        } catch (HttpResponseException $e) {
            return $e->getResponse();
        }
    }

    /**
     * Checks whether the route's action is a controller.
	 * 检查路由动作是否是控制器
     *
     * @return bool
     */
    protected function isControllerAction()
    {
        return is_string($this->action['uses']);
    }

    /**
     * Run the route action and return the response.
	 * 运行路由操作返回响应
     *
     * @return mixed
     */
    protected function runCallable()
    {
        $callable = $this->action['uses'];

        return $callable(...array_values($this->resolveMethodDependencies(
            $this->parametersWithoutNulls(), new ReflectionFunction($this->action['uses'])
        )));
    }

    /**
     * Run the route action and return the response.
	 * 运行路由动作并返回响应
     *
     * @return mixed
     *
     * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
     */
    protected function runController()
    {
        return $this->controllerDispatcher()->dispatch(
            $this, $this->getController(), $this->getControllerMethod()
        );
    }

    /**
     * Get the controller instance for the route.
	 * 得到控制器实例
     *
     * @return mixed
     */
    public function getController()
    {
        if (! $this->controller) {
            $class = $this->parseControllerCallback()[0];

            $this->controller = $this->container->make(ltrim($class, '\\'));
        }

        return $this->controller;
    }

    /**
     * Get the controller method used for the route.
	 * 得到控制器方法
     *
     * @return string
     */
    protected function getControllerMethod()
    {
        return $this->parseControllerCallback()[1];
    }

    /**
     * Parse the controller.
	 * 解析控制器
     *
     * @return array
     */
    protected function parseControllerCallback()
    {
        return Str::parseCallback($this->action['uses']);
    }

    /**
     * Determine if the route matches given request.
	 * 确定路由是否与给定请求匹配
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  bool  $includingMethod
     * @return bool
     */
    public function matches(Request $request, $includingMethod = true)
    {
        $this->compileRoute();

        foreach ($this->getValidators() as $validator) {
            if (! $includingMethod && $validator instanceof MethodValidator) {
                continue;
            }

            if (! $validator->matches($this, $request)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Compile the route into a Symfony CompiledRoute instance.
	 * 将路由编译成一个Symfony CompiledRoute实例
     *
     * @return \Symfony\Component\Routing\CompiledRoute
     */
    protected function compileRoute()
    {
        if (! $this->compiled) {
            $this->compiled = (new RouteCompiler($this))->compile();
        }

        return $this->compiled;
    }

    /**
     * Bind the route to a given request for execution.
	 * 绑定路由至给定的执行请求
     *
     * @param  \Illuminate\Http\Request  $request
     * @return $this
     */
    public function bind(Request $request)
    {
        $this->compileRoute();

        $this->parameters = (new RouteParameterBinder($this))
                        ->parameters($request);

        $this->originalParameters = $this->parameters;

        return $this;
    }

    /**
     * Determine if the route has parameters.
	 * 确定路由是否有参数
     *
     * @return bool
     */
    public function hasParameters()
    {
        return isset($this->parameters);
    }

    /**
     * Determine a given parameter exists from the route.
	 * 确定给定参数是否存在
     *
     * @param  string  $name
     * @return bool
     */
    public function hasParameter($name)
    {
        if ($this->hasParameters()) {
            return array_key_exists($name, $this->parameters());
        }

        return false;
    }

    /**
     * Get a given parameter from the route.
	 * 得到给定参数
     *
     * @param  string  $name
     * @param  mixed  $default
     * @return string|object
     */
    public function parameter($name, $default = null)
    {
        return Arr::get($this->parameters(), $name, $default);
    }

    /**
     * Get original value of a given parameter from the route.
	 * 得到给定参数的原始值
     *
     * @param  string  $name
     * @param  mixed  $default
     * @return string
     */
    public function originalParameter($name, $default = null)
    {
        return Arr::get($this->originalParameters(), $name, $default);
    }

    /**
     * Set a parameter to the given value.
	 * 设置参数为给定值
     *
     * @param  string  $name
     * @param  mixed  $value
     * @return void
     */
    public function setParameter($name, $value)
    {
        $this->parameters();

        $this->parameters[$name] = $value;
    }

    /**
     * Unset a parameter on the route if it is set.
	 * 注销该参数如果路由上设置了
     *
     * @param  string  $name
     * @return void
     */
    public function forgetParameter($name)
    {
        $this->parameters();

        unset($this->parameters[$name]);
    }

    /**
     * Get the key / value list of parameters for the route.
	 * 得到路由参数的键值
     *
     * @return array
     *
     * @throws \LogicException
     */
    public function parameters()
    {
        if (isset($this->parameters)) {
            return $this->parameters;
        }

        throw new LogicException('Route is not bound.');
    }

    /**
     * Get the key / value list of original parameters for the route.
	 * 得到路由原始参数的键值列表
     *
     * @return array
     *
     * @throws \LogicException
     */
    public function originalParameters()
    {
        if (isset($this->originalParameters)) {
            return $this->originalParameters;
        }

        throw new LogicException('Route is not bound.');
    }

    /**
     * Get the key / value list of parameters without null values.
	 * 得到不带空值的参数的键/值列表
     *
     * @return array
     */
    public function parametersWithoutNulls()
    {
        return array_filter($this->parameters(), function ($p) {
            return ! is_null($p);
        });
    }

    /**
     * Get all of the parameter names for the route.
	 * 得到路由的所有参数名
     *
     * @return array
     */
    public function parameterNames()
    {
        if (isset($this->parameterNames)) {
            return $this->parameterNames;
        }

        return $this->parameterNames = $this->compileParameterNames();
    }

    /**
     * Get the parameter names for the route.
	 * 得到路由的参数名
     *
     * @return array
     */
    protected function compileParameterNames()
    {
        preg_match_all('/\{(.*?)\}/', $this->getDomain().$this->uri, $matches);

        return array_map(function ($m) {
            return trim($m, '?');
        }, $matches[1]);
    }

    /**
     * Get the parameters that are listed in the route / controller signature.
	 * 得到路由/控制器签名中列出的参数
     *
     * @param  string|null  $subClass
     * @return array
     */
    public function signatureParameters($subClass = null)
    {
        return RouteSignatureParameters::fromAction($this->action, $subClass);
    }

    /**
     * Set a default value for the route.
	 * 设置路由默认值 
     *
     * @param  string  $key
     * @param  mixed  $value
     * @return $this
     */
    public function defaults($key, $value)
    {
        $this->defaults[$key] = $value;

        return $this;
    }

    /**
     * Set a regular expression requirement on the route.
	 * 在路由上配置正则表达式
     *
     * @param  array|string  $name
     * @param  string|null  $expression
     * @return $this
     */
    public function where($name, $expression = null)
    {
        foreach ($this->parseWhere($name, $expression) as $name => $expression) {
            $this->wheres[$name] = $expression;
        }

        return $this;
    }

    /**
     * Parse arguments to the where method into an array.
	 * 将where方法的参数解析到数组中
     *
     * @param  array|string  $name
     * @param  string  $expression
     * @return array
     */
    protected function parseWhere($name, $expression)
    {
        return is_array($name) ? $name : [$name => $expression];
    }

    /**
     * Set a list of regular expression requirements on the route.
	 * 置路由上的正则表达式需求列表
     *
     * @param  array  $wheres
     * @return $this
     */
    protected function whereArray(array $wheres)
    {
        foreach ($wheres as $name => $expression) {
            $this->where($name, $expression);
        }

        return $this;
    }

    /**
     * Mark this route as a fallback route.
	 * 标记这条路线为退路
     *
     * @return $this
     */
    public function fallback()
    {
        $this->isFallback = true;

        return $this;
    }

    /**
     * Get the HTTP verbs the route responds to.
	 * 得到路由响应的HTTP动词
     *
     * @return array
     */
    public function methods()
    {
        return $this->methods;
    }

    /**
     * Determine if the route only responds to HTTP requests.
	 * 确定路由是否只响应HTTP请求
     *
     * @return bool
     */
    public function httpOnly()
    {
        return in_array('http', $this->action, true);
    }

    /**
     * Determine if the route only responds to HTTPS requests.
	 * 确定路由是否只响应HTTPS请求
     *
     * @return bool
     */
    public function httpsOnly()
    {
        return $this->secure();
    }

    /**
     * Determine if the route only responds to HTTPS requests.
	 * 确定路由是否只响应HTTPS请求？
     *
     * @return bool
     */
    public function secure()
    {
        return in_array('https', $this->action, true);
    }

    /**
     * Get or set the domain for the route.
	 * 得到或设置路由的域
     *
     * @param  string|null  $domain
     * @return $this|string|null
     */
    public function domain($domain = null)
    {
        if (is_null($domain)) {
            return $this->getDomain();
        }

        $this->action['domain'] = $domain;

        return $this;
    }

    /**
     * Get the domain defined for the route.
	 * 得到为路由定义的域
     *
     * @return string|null
     */
    public function getDomain()
    {
        return isset($this->action['domain'])
                ? str_replace(['http://', 'https://'], '', $this->action['domain']) : null;
    }

    /**
     * Get the prefix of the route instance.
	 * 得到路由实例前缀
     *
     * @return string
     */
    public function getPrefix()
    {
        return $this->action['prefix'] ?? null;
    }

    /**
     * Add a prefix to the route URI.
	 * 添加前缀为路由URI
     *
     * @param  string  $prefix
     * @return $this
     */
    public function prefix($prefix)
    {
        $uri = rtrim($prefix, '/').'/'.ltrim($this->uri, '/');

        $this->uri = trim($uri, '/');

        return $this;
    }

    /**
     * Get the URI associated with the route.
	 * 得到与路由关联的URI
     *
     * @return string
     */
    public function uri()
    {
        return $this->uri;
    }

    /**
     * Set the URI that the route responds to.
	 * 设置路由响应的URI
     *
     * @param  string  $uri
     * @return $this
     */
    public function setUri($uri)
    {
        $this->uri = $uri;

        return $this;
    }

    /**
     * Get the name of the route instance.
	 * 得到路由实例名称
     *
     * @return string
     */
    public function getName()
    {
        return $this->action['as'] ?? null;
    }

    /**
     * Add or change the route name.
	 * 添加或修改路由名
     *
     * @param  string  $name
     * @return $this
     */
    public function name($name)
    {
        $this->action['as'] = isset($this->action['as']) ? $this->action['as'].$name : $name;

        return $this;
    }

    /**
     * Determine whether the route's name matches the given patterns.
	 * 确定路由的名称是否与给定的模式匹配
     *
     * @param  mixed  ...$patterns
     * @return bool
     */
    public function named(...$patterns)
    {
        if (is_null($routeName = $this->getName())) {
            return false;
        }

        foreach ($patterns as $pattern) {
            if (Str::is($pattern, $routeName)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Set the handler for the route.
	 * 设置路由的处理程序
     *
     * @param  \Closure|string  $action
     * @return $this
     */
    public function uses($action)
    {
        $action = is_string($action) ? $this->addGroupNamespaceToStringUses($action) : $action;

        return $this->setAction(array_merge($this->action, $this->parseAction([
            'uses' => $action,
            'controller' => $action,
        ])));
    }

    /**
     * Parse a string based action for the "uses" fluent method.
	 * 解析一个基于字符串的动作为"uses"流畅方法
     *
     * @param  string  $action
     * @return string
     */
    protected function addGroupNamespaceToStringUses($action)
    {
        $groupStack = last($this->router->getGroupStack());

        if (isset($groupStack['namespace']) && strpos($action, '\\') !== 0) {
            return $groupStack['namespace'].'\\'.$action;
        }

        return $action;
    }

    /**
     * Get the action name for the route.
	 * 得到路由动作名
     *
     * @return string
     */
    public function getActionName()
    {
        return $this->action['controller'] ?? 'Closure';
    }

    /**
     * Get the method name of the route action.
	 * 得到路由动作方法名
     *
     * @return string
     */
    public function getActionMethod()
    {
        return Arr::last(explode('@', $this->getActionName()));
    }

    /**
     * Get the action array or one of its properties for the route.
	 * 获取该路由的动作数组或其中一个属性
     *
     * @param  string|null  $key
     * @return mixed
     */
    public function getAction($key = null)
    {
        return Arr::get($this->action, $key);
    }

    /**
     * Set the action array for the route.
	 * 设置路由的动作数组
     *
     * @param  array  $action
     * @return $this
     */
    public function setAction(array $action)
    {
        $this->action = $action;

        return $this;
    }

    /**
     * Get all middleware, including the ones from the controller.
	 * 得到所有中间件，包括来自控制器的那些
     *
     * @return array
     */
    public function gatherMiddleware()
    {
        if (! is_null($this->computedMiddleware)) {
            return $this->computedMiddleware;
        }

        $this->computedMiddleware = [];

        return $this->computedMiddleware = Router::uniqueMiddleware(array_merge(
            $this->middleware(), $this->controllerMiddleware()
        ));
    }

    /**
     * Get or set the middlewares attached to the route.
	 * 得到或设置附加到路由的中间件
     *
     * @param  array|string|null  $middleware
     * @return $this|array
     */
    public function middleware($middleware = null)
    {
        if (is_null($middleware)) {
            return (array) ($this->action['middleware'] ?? []);
        }

        if (is_string($middleware)) {
            $middleware = func_get_args();
        }

        $this->action['middleware'] = array_merge(
            (array) ($this->action['middleware'] ?? []), $middleware
        );

        return $this;
    }

    /**
     * Get the middleware for the route's controller.
	 * 得到路由控制器的中间件
     *
     * @return array
     */
    public function controllerMiddleware()
    {
        if (! $this->isControllerAction()) {
            return [];
        }

        return $this->controllerDispatcher()->getMiddleware(
            $this->getController(), $this->getControllerMethod()
        );
    }

    /**
     * Get the dispatcher for the route's controller.
	 * 得到路由控制器的调度程序
     *
     * @return \Illuminate\Routing\Contracts\ControllerDispatcher
     */
    public function controllerDispatcher()
    {
        if ($this->container->bound(ControllerDispatcherContract::class)) {
            return $this->container->make(ControllerDispatcherContract::class);
        }

        return new ControllerDispatcher($this->container);
    }

    /**
     * Get the route validators for the instance.
	 * 得到实例的路由验证器
     *
     * @return array
     */
    public static function getValidators()
    {
        if (isset(static::$validators)) {
            return static::$validators;
        }

        // To match the route, we will use a chain of responsibility pattern with the
        // validator implementations. We will spin through each one making sure it
        // passes and then we will know if the route as a whole matches request.
		// 为了匹配路由，我们将在验证器实现中使用责任链模式。
		// 我们将旋转每个路径，确保其通过，然后我们将知道整个路径是否符合请求。
        return static::$validators = [
            new UriValidator, new MethodValidator,
            new SchemeValidator, new HostValidator,
        ];
    }

    /**
     * Get the compiled version of the route.
	 * 得到路由的编译版本
     *
     * @return \Symfony\Component\Routing\CompiledRoute
     */
    public function getCompiled()
    {
        return $this->compiled;
    }

    /**
     * Set the router instance on the route.
	 * 设置路由实例在路由上
     *
     * @param  \Illuminate\Routing\Router  $router
     * @return $this
     */
    public function setRouter(Router $router)
    {
        $this->router = $router;

        return $this;
    }

    /**
     * Set the container instance on the route.
	 * 设置容器实例在路由上
     *
     * @param  \Illuminate\Container\Container  $container
     * @return $this
     */
    public function setContainer(Container $container)
    {
        $this->container = $container;

        return $this;
    }

    /**
     * Prepare the route instance for serialization.
	 * 为序列化准备路由实例
     *
     * @return void
     *
     * @throws \LogicException
     */
    public function prepareForSerialization()
    {
        if ($this->action['uses'] instanceof Closure) {
            throw new LogicException("Unable to prepare route [{$this->uri}] for serialization. Uses Closure.");
        }

        $this->compileRoute();

        unset($this->router, $this->container);
    }

    /**
     * Dynamically access route parameters.
	 * 动态访问路由参数
     *
     * @param  string  $key
     * @return mixed
     */
    public function __get($key)
    {
        return $this->parameter($key);
    }
}

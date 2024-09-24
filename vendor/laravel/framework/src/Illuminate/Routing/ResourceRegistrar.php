<?php
/**
 * 路由资源注册
 */

namespace Illuminate\Routing;

use Illuminate\Support\Str;

class ResourceRegistrar
{
    /**
     * The router instance.
	 * 路由实例
     *
     * @var \Illuminate\Routing\Router
     */
    protected $router;

    /**
     * The default actions for a resourceful controller.
	 * 默认动作资源控制器
     *
     * @var array
     */
    protected $resourceDefaults = ['index', 'create', 'store', 'show', 'edit', 'update', 'destroy'];

    /**
     * The parameters set for this resource instance.
	 * 设置参数为此资源实例
     *
     * @var array|string
     */
    protected $parameters;

    /**
     * The global parameter mapping.
	 * 全局参数映射
     *
     * @var array
     */
    protected static $parameterMap = [];

    /**
     * Singular global parameters.
	 * 单独全局参数
     *
     * @var bool
     */
    protected static $singularParameters = true;

    /**
     * The verbs used in the resource URIs.
	 * 动作在资源URI里使用
     *
     * @var array
     */
    protected static $verbs = [
        'create' => 'create',
        'edit' => 'edit',
    ];

    /**
     * Create a new resource registrar instance.
	 * 创建新的资源注册实例
     *
     * @param  \Illuminate\Routing\Router  $router
     * @return void
     */
    public function __construct(Router $router)
    {
        $this->router = $router;
    }

    /**
     * Route a resource to a controller.
	 * 路由资源至控制器
     *
     * @param  string  $name
     * @param  string  $controller
     * @param  array  $options
     * @return \Illuminate\Routing\RouteCollection
     */
    public function register($name, $controller, array $options = [])
    {
        if (isset($options['parameters']) && ! isset($this->parameters)) {
            $this->parameters = $options['parameters'];
        }

        // If the resource name contains a slash, we will assume the developer wishes to
        // register these resource routes with a prefix so we will set that up out of
        // the box so they don't have to mess with it. Otherwise, we will continue.
		// 如果资源名称包含斜线，我们将假设开发人员希望用前缀注册这些资源路由，
		// 这样我们就可以开箱即用地设置，这样他们就不必弄乱它。否则，我们将继续。
        if (Str::contains($name, '/')) {
            $this->prefixedResource($name, $controller, $options);

            return;
        }

        // We need to extract the base resource from the resource name. Nested resources
        // are supported in the framework, but we need to know what name to use for a
        // place-holder on the route parameters, which should be the base resources.
		// 我们需要从资源名称中提取基础资源。
		// 框架中支持嵌套资源，但我们需要知道在路由参数上为占位符使用什么名称，这应该是基础资源。
        $base = $this->getResourceWildcard(last(explode('.', $name)));

        $defaults = $this->resourceDefaults;

        $collection = new RouteCollection;

        foreach ($this->getResourceMethods($defaults, $options) as $m) {
            $collection->add($this->{'addResource'.ucfirst($m)}(
                $name, $base, $controller, $options
            ));
        }

        return $collection;
    }

    /**
     * Build a set of prefixed resource routes.
	 * 创建一组前缀资源路由
     *
     * @param  string  $name
     * @param  string  $controller
     * @param  array  $options
     * @return void
     */
    protected function prefixedResource($name, $controller, array $options)
    {
        [$name, $prefix] = $this->getResourcePrefix($name);

        // We need to extract the base resource from the resource name. Nested resources
        // are supported in the framework, but we need to know what name to use for a
        // place-holder on the route parameters, which should be the base resources.
		// 我们需要从资源名称中提取基础资源。
		// 框架中支持嵌套资源，但我们需要知道在路由参数上为占位符使用什么名称，这应该是基础资源。
        $callback = function ($me) use ($name, $controller, $options) {
            $me->resource($name, $controller, $options);
        };

        return $this->router->group(compact('prefix'), $callback);
    }

    /**
     * Extract the resource and prefix from a resource name.
	 * 提取资源和前缀从资源名称中
     *
     * @param  string  $name
     * @return array
     */
    protected function getResourcePrefix($name)
    {
        $segments = explode('/', $name);

        // To get the prefix, we will take all of the name segments and implode them on
        // a slash. This will generate a proper URI prefix for us. Then we take this
        // last segment, which will be considered the final resources name we use.
		// 为了获得前缀，我们将获取所有名称段，并在斜线上内爆它们。
		// 这将为我们生成一个正确的URI前缀。然后我们取最后一段，这将被视为我们使用的最终资源名称。
        $prefix = implode('/', array_slice($segments, 0, -1));

        return [end($segments), $prefix];
    }

    /**
     * Get the applicable resource methods.
	 * 得到应用资源方法
     *
     * @param  array  $defaults
     * @param  array  $options
     * @return array
     */
    protected function getResourceMethods($defaults, $options)
    {
        $methods = $defaults;

        if (isset($options['only'])) {
            $methods = array_intersect($methods, (array) $options['only']);
        }

        if (isset($options['except'])) {
            $methods = array_diff($methods, (array) $options['except']);
        }

        return $methods;
    }

    /**
     * Add the index method for a resourceful route.
	 * 增加资源路由的索引方法
     *
     * @param  string  $name
     * @param  string  $base
     * @param  string  $controller
     * @param  array  $options
     * @return \Illuminate\Routing\Route
     */
    protected function addResourceIndex($name, $base, $controller, $options)
    {
        $uri = $this->getResourceUri($name);

        $action = $this->getResourceAction($name, $controller, 'index', $options);

        return $this->router->get($uri, $action);
    }

    /**
     * Add the create method for a resourceful route.
	 * 添加资源create方法
     *
     * @param  string  $name
     * @param  string  $base
     * @param  string  $controller
     * @param  array  $options
     * @return \Illuminate\Routing\Route
     */
    protected function addResourceCreate($name, $base, $controller, $options)
    {
        $uri = $this->getResourceUri($name).'/'.static::$verbs['create'];

        $action = $this->getResourceAction($name, $controller, 'create', $options);

        return $this->router->get($uri, $action);
    }

    /**
     * Add the store method for a resourceful route.
	 * 为资源路由添加store方法
     *
     * @param  string  $name
     * @param  string  $base
     * @param  string  $controller
     * @param  array  $options
     * @return \Illuminate\Routing\Route
     */
    protected function addResourceStore($name, $base, $controller, $options)
    {
        $uri = $this->getResourceUri($name);

        $action = $this->getResourceAction($name, $controller, 'store', $options);

        return $this->router->post($uri, $action);
    }

    /**
     * Add the show method for a resourceful route.
	 * 为资源路由增加show方法
     *
     * @param  string  $name
     * @param  string  $base
     * @param  string  $controller
     * @param  array  $options
     * @return \Illuminate\Routing\Route
     */
    protected function addResourceShow($name, $base, $controller, $options)
    {
        $name = $this->getShallowName($name, $options);

        $uri = $this->getResourceUri($name).'/{'.$base.'}';

        $action = $this->getResourceAction($name, $controller, 'show', $options);

        return $this->router->get($uri, $action);
    }

    /**
     * Add the edit method for a resourceful route.
	 * 为资源路由增加edit方法
     *
     * @param  string  $name
     * @param  string  $base
     * @param  string  $controller
     * @param  array  $options
     * @return \Illuminate\Routing\Route
     */
    protected function addResourceEdit($name, $base, $controller, $options)
    {
        $name = $this->getShallowName($name, $options);

        $uri = $this->getResourceUri($name).'/{'.$base.'}/'.static::$verbs['edit'];

        $action = $this->getResourceAction($name, $controller, 'edit', $options);

        return $this->router->get($uri, $action);
    }

    /**
     * Add the update method for a resourceful route.
	 * 为资源路由增加update方法
     *
     * @param  string  $name
     * @param  string  $base
     * @param  string  $controller
     * @param  array  $options
     * @return \Illuminate\Routing\Route
     */
    protected function addResourceUpdate($name, $base, $controller, $options)
    {
        $name = $this->getShallowName($name, $options);

        $uri = $this->getResourceUri($name).'/{'.$base.'}';

        $action = $this->getResourceAction($name, $controller, 'update', $options);

        return $this->router->match(['PUT', 'PATCH'], $uri, $action);
    }

    /**
     * Add the destroy method for a resourceful route.
	 * 为资源路由添加destroy方法
     *
     * @param  string  $name
     * @param  string  $base
     * @param  string  $controller
     * @param  array  $options
     * @return \Illuminate\Routing\Route
     */
    protected function addResourceDestroy($name, $base, $controller, $options)
    {
        $name = $this->getShallowName($name, $options);

        $uri = $this->getResourceUri($name).'/{'.$base.'}';

        $action = $this->getResourceAction($name, $controller, 'destroy', $options);

        return $this->router->delete($uri, $action);
    }

    /**
     * Get the name for a given resource with shallowness applied when applicable.
	 * 得到给定资源的名称，并在适用时应用浅度。
     *
     * @param  string  $name
     * @param  array  $options
     * @return string
     */
    protected function getShallowName($name, $options)
    {
        return isset($options['shallow']) && $options['shallow']
                    ? last(explode('.', $name))
                    : $name;
    }

    /**
     * Get the base resource URI for a given resource.
	 * 得到基本资源URI
     *
     * @param  string  $resource
     * @return string
     */
    public function getResourceUri($resource)
    {
        if (! Str::contains($resource, '.')) {
            return $resource;
        }

        // Once we have built the base URI, we'll remove the parameter holder for this
        // base resource name so that the individual route adders can suffix these
        // paths however they need to, as some do not have any parameters at all.
		// 一旦我们构建了基本URI，我们将删除此基本资源名称的参数持有者，
		// 以便各个路由加法器可以根据需要为这些路径添加后缀，因为有些路径根本没有任何参数。
        $segments = explode('.', $resource);

        $uri = $this->getNestedResourceUri($segments);

        return str_replace('/{'.$this->getResourceWildcard(end($segments)).'}', '', $uri);
    }

    /**
     * Get the URI for a nested resource segment array.
	 * 得到嵌套资源段数组的URI
     *
     * @param  array  $segments
     * @return string
     */
    protected function getNestedResourceUri(array $segments)
    {
        // We will spin through the segments and create a place-holder for each of the
        // resource segments, as well as the resource itself. Then we should get an
        // entire string for the resource URI that contains all nested resources.
		// 我们将遍历这些片段，并为每个资源片段以及资源本身创建一个占位符。
		// 然后，我们应该得到一个包含所有嵌套资源的资源URI的完整字符串。
        return implode('/', array_map(function ($s) {
            return $s.'/{'.$this->getResourceWildcard($s).'}';
        }, $segments));
    }

    /**
     * Format a resource parameter for usage.
	 * 格式化资源参数以供使用
     *
     * @param  string  $value
     * @return string
     */
    public function getResourceWildcard($value)
    {
        if (isset($this->parameters[$value])) {
            $value = $this->parameters[$value];
        } elseif (isset(static::$parameterMap[$value])) {
            $value = static::$parameterMap[$value];
        } elseif ($this->parameters === 'singular' || static::$singularParameters) {
            $value = Str::singular($value);
        }

        return str_replace('-', '_', $value);
    }

    /**
     * Get the action array for a resource route.
	 * 得到操作数组为资源路由
     *
     * @param  string  $resource
     * @param  string  $controller
     * @param  string  $method
     * @param  array  $options
     * @return array
     */
    protected function getResourceAction($resource, $controller, $method, $options)
    {
        $name = $this->getResourceRouteName($resource, $method, $options);

        $action = ['as' => $name, 'uses' => $controller.'@'.$method];

        if (isset($options['middleware'])) {
            $action['middleware'] = $options['middleware'];
        }

        return $action;
    }

    /**
     * Get the name for a given resource.
	 * 得到资源名称
     *
     * @param  string  $resource
     * @param  string  $method
     * @param  array  $options
     * @return string
     */
    protected function getResourceRouteName($resource, $method, $options)
    {
        $name = $resource;

        // If the names array has been provided to us we will check for an entry in the
        // array first. We will also check for the specific method within this array
        // so the names may be specified on a more "granular" level using methods.
		// 如果名称数组已提供给我们，我们将首先检查数组中的条目。
		// 我们还将检查此数组中的特定方法，以便可以使用方法在更"精细"的级别上指定名称。
        if (isset($options['names'])) {
            if (is_string($options['names'])) {
                $name = $options['names'];
            } elseif (isset($options['names'][$method])) {
                return $options['names'][$method];
            }
        }

        // If a global prefix has been assigned to all names for this resource, we will
        // grab that so we can prepend it onto the name when we create this name for
        // the resource action. Otherwise we'll just use an empty string for here.
		// 如果已为此资源的所有名称分配了全局前缀，我们将获取该前缀，
		// 以便在为资源操作创建此名称时将其添加到名称前。否则，我们将在此处使用空字符串。
        $prefix = isset($options['as']) ? $options['as'].'.' : '';

        return trim(sprintf('%s%s.%s', $prefix, $name, $method), '.');
    }

    /**
     * Set or unset the unmapped global parameters to singular.
	 * 设置或取消设置未映射的全局参数为单数
     *
     * @param  bool  $singular
     * @return void
     */
    public static function singularParameters($singular = true)
    {
        static::$singularParameters = (bool) $singular;
    }

    /**
     * Get the global parameter map.
	 * 得到全局参数映射
     *
     * @return array
     */
    public static function getParameters()
    {
        return static::$parameterMap;
    }

    /**
     * Set the global parameter mapping.
	 * 设置全局参数映射
     *
     * @param  array  $parameters
     * @return void
     */
    public static function setParameters(array $parameters = [])
    {
        static::$parameterMap = $parameters;
    }

    /**
     * Get or set the action verbs used in the resource URIs.
	 * 得到或设置资源URI中使用的动作动词
     *
     * @param  array  $verbs
     * @return array
     */
    public static function verbs(array $verbs = [])
    {
        if (empty($verbs)) {
            return static::$verbs;
        } else {
            static::$verbs = array_merge(static::$verbs, $verbs);
        }
    }
}

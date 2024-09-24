<?php
/**
 * 路由集合
 */

namespace Illuminate\Routing;

use ArrayIterator;
use Countable;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Arr;
use IteratorAggregate;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class RouteCollection implements Countable, IteratorAggregate
{
    /**
     * An array of the routes keyed by method.
	 * 路由键值数组
     *
     * @var array
     */
    protected $routes = [];

    /**
     * A flattened array of all of the routes.
	 * 所有路由
     *
     * @var array
     */
    protected $allRoutes = [];

    /**
     * A look-up table of routes by their names.
	 * 按名称查找路由表
     *
     * @var array
     */
    protected $nameList = [];

    /**
     * A look-up table of routes by controller action.
	 * 根据控制器动作的路由查找表
     *
     * @var array
     */
    protected $actionList = [];

    /**
     * Add a Route instance to the collection.
	 * 添加路由实例至集合
     *
     * @param  \Illuminate\Routing\Route  $route
     * @return \Illuminate\Routing\Route
     */
    public function add(Route $route)
    {
        $this->addToCollections($route);

        $this->addLookups($route);

        return $route;
    }

    /**
     * Add the given route to the arrays of routes.
	 * 添加路由至给定的路由数组中
     *
     * @param  \Illuminate\Routing\Route  $route
     * @return void
     */
    protected function addToCollections($route)
    {
        $domainAndUri = $route->getDomain().$route->uri();

        foreach ($route->methods() as $method) {
            $this->routes[$method][$domainAndUri] = $route;
        }

        $this->allRoutes[$method.$domainAndUri] = $route;
    }

    /**
     * Add the route to any look-up tables if necessary.
	 * 将该路由添加到任何查找表中如有必要
     *
     * @param  \Illuminate\Routing\Route  $route
     * @return void
     */
    protected function addLookups($route)
    {
        // If the route has a name, we will add it to the name look-up table so that we
        // will quickly be able to find any route associate with a name and not have
        // to iterate through every route every time we need to perform a look-up.
		// 如果路由有一个名称，我们会将其添加到名称查找表中，这样我们就可以快速找到与名称关联的任何路由，
		// 而不必每次需要执行查找时都迭代每条路由。
        if ($name = $route->getName()) {
            $this->nameList[$name] = $route;
        }

        // When the route is routing to a controller we will also store the action that
        // is used by the route. This will let us reverse route to controllers while
        // processing a request and easily generate URLs to the given controllers.
		// 当路由路由到控制器时，我们还将存储路由使用的操作。
		// 这将使我们在处理请求时反向路由到控制器，并轻松生成指向给定控制器的URL。
        $action = $route->getAction();

        if (isset($action['controller'])) {
            $this->addToActionList($action, $route);
        }
    }

    /**
     * Add a route to the controller action dictionary.
	 * 添加到控制器动作字典的路由
     *
     * @param  array  $action
     * @param  \Illuminate\Routing\Route  $route
     * @return void
     */
    protected function addToActionList($action, $route)
    {
        $this->actionList[trim($action['controller'], '\\')] = $route;
    }

    /**
     * Refresh the name look-up table.
	 * 刷新名称查找表
     *
     * This is done in case any names are fluently defined or if routes are overwritten.
     *
     * @return void
     */
    public function refreshNameLookups()
    {
        $this->nameList = [];

        foreach ($this->allRoutes as $route) {
            if ($route->getName()) {
                $this->nameList[$route->getName()] = $route;
            }
        }
    }

    /**
     * Refresh the action look-up table.
	 * 刷新操作查找表
     *
     * This is done in case any actions are overwritten with new controllers.
     *
     * @return void
     */
    public function refreshActionLookups()
    {
        $this->actionList = [];

        foreach ($this->allRoutes as $route) {
            if (isset($route->getAction()['controller'])) {
                $this->addToActionList($route->getAction(), $route);
            }
        }
    }

    /**
     * Find the first route matching a given request.
	 * 找到与给定请求匹配的第一条路由
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Routing\Route
     *
     * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
     */
    public function match(Request $request)
    {
        $routes = $this->get($request->getMethod());

        // First, we will see if we can find a matching route for this current request
        // method. If we can, great, we can just return it so that it can be called
        // by the consumer. Otherwise we will check for routes with another verb.
		// 首先，我们将看看是否可以为当前的请求方法找到匹配的路由。
		// 如果可以的话，很好，我们可以把它退回，这样消费者就可以调用它了。否则，我们将使用另一个动词检查路由。
        $route = $this->matchAgainstRoutes($routes, $request);

        if (! is_null($route)) {
            return $route->bind($request);
        }

        // If no route was found we will now check if a matching route is specified by
        // another HTTP verb. If it is we will need to throw a MethodNotAllowed and
        // inform the user agent of which HTTP verb it should use for this route.
		// 如果没有找到路由，我们现在将检查另一个HTTP谓词是否指定了匹配的路由。
		// 如果是这样，我们需要抛出一个MethodNotAllowed，并通知用户代理它应该为此路由使用哪个HTTP动词。
        $others = $this->checkForAlternateVerbs($request);

        if (count($others) > 0) {
            return $this->getRouteForMethods($request, $others);
        }

        throw new NotFoundHttpException;
    }

    /**
     * Determine if a route in the array matches the request.
	 * 确定数组中的路由是否与请求匹配
     *
     * @param  array  $routes
     * @param  \Illuminate\Http\Request  $request
     * @param  bool  $includingMethod
     * @return \Illuminate\Routing\Route|null
     */
    protected function matchAgainstRoutes(array $routes, $request, $includingMethod = true)
    {
        [$fallbacks, $routes] = collect($routes)->partition(function ($route) {
            return $route->isFallback;
        });

        return $routes->merge($fallbacks)->first(function ($value) use ($request, $includingMethod) {
            return $value->matches($request, $includingMethod);
        });
    }

    /**
     * Determine if any routes match on another HTTP verb.
	 * 确定是否有任何路由与另一个HTTP动作匹配
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    protected function checkForAlternateVerbs($request)
    {
        $methods = array_diff(Router::$verbs, [$request->getMethod()]);

        // Here we will spin through all verbs except for the current request verb and
        // check to see if any routes respond to them. If they do, we will return a
        // proper error response with the correct headers on the response string.
		// 在这里，我们将浏览除当前请求动词之外的所有动词，并检查是否有任何路由对其做出响应。
		// 如果他们这样做，我们将返回一个正确的错误响应，并在响应字符串中包含正确的标头。
        $others = [];

        foreach ($methods as $method) {
            if (! is_null($this->matchAgainstRoutes($this->get($method), $request, false))) {
                $others[] = $method;
            }
        }

        return $others;
    }

    /**
     * Get a route (if necessary) that responds when other available methods are present.
	 * 得到一个路由(如果有必要)，当存在其他可用方法时，它会做出响应。
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  array  $methods
     * @return \Illuminate\Routing\Route
     *
     * @throws \Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException
     */
    protected function getRouteForMethods($request, array $methods)
    {
        if ($request->method() === 'OPTIONS') {
            return (new Route('OPTIONS', $request->path(), function () use ($methods) {
                return new Response('', 200, ['Allow' => implode(',', $methods)]);
            }))->bind($request);
        }

        $this->methodNotAllowed($methods, $request->method());
    }

    /**
     * Throw a method not allowed HTTP exception.
	 * 抛出不允许方法HTTP异常
     *
     * @param  array  $others
     * @param  string  $method
     * @return void
     *
     * @throws \Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException
     */
    protected function methodNotAllowed(array $others, $method)
    {
        throw new MethodNotAllowedHttpException(
            $others,
            sprintf(
                'The %s method is not supported for this route. Supported methods: %s.',
                $method,
                implode(', ', $others)
            )
        );
    }

    /**
     * Get routes from the collection by method.
	 * 得到路由从集合中通过方法
     *
     * @param  string|null  $method
     * @return array
     */
    public function get($method = null)
    {
        return is_null($method) ? $this->getRoutes() : Arr::get($this->routes, $method, []);
    }

    /**
     * Determine if the route collection contains a given named route.
	 * 确定路由集合是否包含给定的命名路由
     *
     * @param  string  $name
     * @return bool
     */
    public function hasNamedRoute($name)
    {
        return ! is_null($this->getByName($name));
    }

    /**
     * Get a route instance by its name.
	 * 得到路由实例通过名称
     *
     * @param  string  $name
     * @return \Illuminate\Routing\Route|null
     */
    public function getByName($name)
    {
        return $this->nameList[$name] ?? null;
    }

    /**
     * Get a route instance by its controller action.
	 * 得到一个路由实例从控制器动作
     *
     * @param  string  $action
     * @return \Illuminate\Routing\Route|null
     */
    public function getByAction($action)
    {
        return $this->actionList[$action] ?? null;
    }

    /**
     * Get all of the routes in the collection.
	 * 得到集合中的所有路由
     *
     * @return array
     */
    public function getRoutes()
    {
        return array_values($this->allRoutes);
    }

    /**
     * Get all of the routes keyed by their HTTP verb / method.
	 * 得到所有由HTTP动词/方法指定的路由
     *
     * @return array
     */
    public function getRoutesByMethod()
    {
        return $this->routes;
    }

    /**
     * Get all of the routes keyed by their name.
	 * 把所有的路线按名字标记
     *
     * @return array
     */
    public function getRoutesByName()
    {
        return $this->nameList;
    }

    /**
     * Get an iterator for the items.
	 * 得到项的迭代器
     *
     * @return \ArrayIterator
     */
    public function getIterator()
    {
        return new ArrayIterator($this->getRoutes());
    }

    /**
     * Count the number of items in the collection.
	 * 计算集合中的数目
     *
     * @return int
     */
    public function count()
    {
        return count($this->getRoutes());
    }
}

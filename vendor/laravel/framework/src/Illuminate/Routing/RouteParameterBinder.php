<?php
/**
 * 路由参数绑定器
 */

namespace Illuminate\Routing;

use Illuminate\Support\Arr;

class RouteParameterBinder
{
    /**
     * The route instance.
	 * 路由实例
     *
     * @var \Illuminate\Routing\Route
     */
    protected $route;

    /**
     * Create a new Route parameter binder instance.
	 * 创建新的路由参数绑定实例
     *
     * @param  \Illuminate\Routing\Route  $route
     * @return void
     */
    public function __construct($route)
    {
        $this->route = $route;
    }

    /**
     * Get the parameters for the route.
	 * 得到路由参数
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function parameters($request)
    {
        // If the route has a regular expression for the host part of the URI, we will
        // compile that and get the parameter matches for this domain. We will then
        // merge them into this parameters array so that this array is completed.
		// 如果路由有URI主机部分的正则表达式，我们将编译它并获取此域的参数匹配。
		// 然后，我们将它们合并到这个参数数组中，这样这个数组就完成了。
        $parameters = $this->bindPathParameters($request);

        // If the route has a regular expression for the host part of the URI, we will
        // compile that and get the parameter matches for this domain. We will then
        // merge them into this parameters array so that this array is completed.
		// 如果路由有URI主机部分的正则表达式，我们将编译它并获取此域的参数匹配。
		// 然后，我们将它们合并到这个参数数组中，这样这个数组就完成了。
        if (! is_null($this->route->compiled->getHostRegex())) {
            $parameters = $this->bindHostParameters(
                $request, $parameters
            );
        }

        return $this->replaceDefaults($parameters);
    }

    /**
     * Get the parameter matches for the path portion of the URI.
	 * 得到URI的路径部分的参数匹配
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    protected function bindPathParameters($request)
    {
        $path = '/'.ltrim($request->decodedPath(), '/');

        preg_match($this->route->compiled->getRegex(), $path, $matches);

        return $this->matchToKeys(array_slice($matches, 1));
    }

    /**
     * Extract the parameter list from the host part of the request.
	 * 提取参数列表从请求的主机部分
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  array  $parameters
     * @return array
     */
    protected function bindHostParameters($request, $parameters)
    {
        preg_match($this->route->compiled->getHostRegex(), $request->getHost(), $matches);

        return array_merge($this->matchToKeys(array_slice($matches, 1)), $parameters);
    }

    /**
     * Combine a set of parameter matches with the route's keys.
	 * 将一组参数匹配与路由的关键字组合起来
     *
     * @param  array  $matches
     * @return array
     */
    protected function matchToKeys(array $matches)
    {
        if (empty($parameterNames = $this->route->parameterNames())) {
            return [];
        }

        $parameters = array_intersect_key($matches, array_flip($parameterNames));

        return array_filter($parameters, function ($value) {
            return is_string($value) && strlen($value) > 0;
        });
    }

    /**
     * Replace null parameters with their defaults.
	 * 用默认值替换空参数
     *
     * @param  array  $parameters
     * @return array
     */
    protected function replaceDefaults(array $parameters)
    {
        foreach ($parameters as $key => $value) {
            $parameters[$key] = $value ?? Arr::get($this->route->defaults, $key);
        }

        foreach ($this->route->defaults as $key => $value) {
            if (! isset($parameters[$key])) {
                $parameters[$key] = $value;
            }
        }

        return $parameters;
    }
}

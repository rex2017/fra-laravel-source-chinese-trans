<?php
/**
 * 路由，匹配方法验证器
 */

namespace Illuminate\Routing\Matching;

use Illuminate\Http\Request;
use Illuminate\Routing\Route;

class MethodValidator implements ValidatorInterface
{
    /**
     * Validate a given rule against a route and request.
	 * 验证给定的规则根据路由和请求
     *
     * @param  \Illuminate\Routing\Route  $route
     * @param  \Illuminate\Http\Request  $request
     * @return bool
     */
    public function matches(Route $route, Request $request)
    {
        return in_array($request->getMethod(), $route->methods());
    }
}

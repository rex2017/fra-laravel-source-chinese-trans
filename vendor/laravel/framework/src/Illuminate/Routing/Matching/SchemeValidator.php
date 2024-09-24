<?php
/**
 * 路由，匹配方案验证器
 */

namespace Illuminate\Routing\Matching;

use Illuminate\Http\Request;
use Illuminate\Routing\Route;

class SchemeValidator implements ValidatorInterface
{
    /**
     * Validate a given rule against a route and request.
	 * 验证给定的规则
     *
     * @param  \Illuminate\Routing\Route  $route
     * @param  \Illuminate\Http\Request  $request
     * @return bool
     */
    public function matches(Route $route, Request $request)
    {
        if ($route->httpOnly()) {
            return ! $request->secure();
        } elseif ($route->secure()) {
            return $request->secure();
        }

        return true;
    }
}

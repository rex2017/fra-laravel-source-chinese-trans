<?php
/**
 * 路由，URI验证
 */

namespace Illuminate\Routing\Matching;

use Illuminate\Http\Request;
use Illuminate\Routing\Route;

class UriValidator implements ValidatorInterface
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
        $path = $request->path() === '/' ? '/' : '/'.$request->path();

        return preg_match($route->getCompiled()->getRegex(), rawurldecode($path));
    }
}

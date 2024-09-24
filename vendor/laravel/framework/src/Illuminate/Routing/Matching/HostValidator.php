<?php
/**
 * 路由，匹配主机验证
 */

namespace Illuminate\Routing\Matching;

use Illuminate\Http\Request;
use Illuminate\Routing\Route;

class HostValidator implements ValidatorInterface
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
        $hostRegex = $route->getCompiled()->getHostRegex();

        if (is_null($hostRegex)) {
            return true;
        }

        return preg_match($hostRegex, $request->getHost());
    }
}

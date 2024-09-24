<?php
/**
 * Http，检查修改响应
 */

namespace Illuminate\Http\Middleware;

use Closure;
use Symfony\Component\HttpFoundation\Response;

class CheckResponseForModifications
{
    /**
     * Handle an incoming request.
	 * 处理传入请求
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        $response = $next($request);

        if ($response instanceof Response) {
            $response->isNotModified($request);
        }

        return $response;
    }
}

<?php
/**
 * Http，框架防护
 */

namespace Illuminate\Http\Middleware;

use Closure;

class FrameGuard
{
    /**
     * Handle the given request and get the response.
	 * 处理给定的请求并获得响应
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function handle($request, Closure $next)
    {
        $response = $next($request);

        $response->headers->set('X-Frame-Options', 'SAMEORIGIN', false);

        return $response;
    }
}

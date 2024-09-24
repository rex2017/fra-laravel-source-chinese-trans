<?php
/**
 * 路由，中间件验证签名
 */

namespace Illuminate\Routing\Middleware;

use Closure;
use Illuminate\Routing\Exceptions\InvalidSignatureException;

class ValidateSignature
{
    /**
     * Handle an incoming request.
	 * 处理传入请求
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return \Illuminate\Http\Response
     *
     * @throws \Illuminate\Routing\Exceptions\InvalidSignatureException
     */
    public function handle($request, Closure $next)
    {
        if ($request->hasValidSignature()) {
            return $next($request);
        }

        throw new InvalidSignatureException;
    }
}

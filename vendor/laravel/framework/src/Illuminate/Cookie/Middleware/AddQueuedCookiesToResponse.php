<?php
/**
 * COOKIE，添加排队cookie至响应中
 */

namespace Illuminate\Cookie\Middleware;

use Closure;
use Illuminate\Contracts\Cookie\QueueingFactory as CookieJar;

class AddQueuedCookiesToResponse
{
    /**
     * The cookie jar instance.
	 * cookie压缩实例
     *
     * @var \Illuminate\Contracts\Cookie\QueueingFactory
     */
    protected $cookies;

    /**
     * Create a new CookieQueue instance.
	 * 创建新的cookie队列实例
     *
     * @param  \Illuminate\Contracts\Cookie\QueueingFactory  $cookies
     * @return void
     */
    public function __construct(CookieJar $cookies)
    {
        $this->cookies = $cookies;
    }

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

        foreach ($this->cookies->getQueuedCookies() as $cookie) {
            $response->headers->setCookie($cookie);
        }

        return $response;
    }
}

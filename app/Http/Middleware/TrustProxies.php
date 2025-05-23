<?php
/**
 * App，Http，真实代理
 */

namespace App\Http\Middleware;

use Fideloper\Proxy\TrustProxies as Middleware;
use Illuminate\Http\Request;

class TrustProxies extends Middleware
{
    /**
     * The trusted proxies for this application.
     * 应用的可信代理
     *
     * @var array|string
     */
    protected $proxies;

    /**
     * The headers that should be used to detect proxies.
     * 应该被用来检测代理头。
     *
     * @var int
     */
    protected $headers = Request::HEADER_X_FORWARDED_ALL;
}

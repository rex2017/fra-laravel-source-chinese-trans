<?php
/**
 * App，Http，中间件，检查维护模式
 */

namespace App\Http\Middleware;

use Illuminate\Foundation\Http\Middleware\CheckForMaintenanceMode as Middleware;

class CheckForMaintenanceMode extends Middleware
{
    /**
     * The URIs that should be reachable while maintenance mode is enabled.
     * 在启用维护模式时，URI应该是可访问的。
     *
     * @var array
     */
    protected $except = [
        //
    ];
}

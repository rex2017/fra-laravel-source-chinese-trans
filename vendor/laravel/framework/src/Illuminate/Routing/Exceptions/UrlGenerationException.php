<?php
/**
 * 路由，URL生成异常
 */

namespace Illuminate\Routing\Exceptions;

use Exception;

class UrlGenerationException extends Exception
{
    /**
     * Create a new exception for missing route parameters.
	 * 创建新的异常丢失路由参数
     *
     * @param  \Illuminate\Routing\Route  $route
     * @return static
     */
    public static function forMissingParameters($route)
    {
        return new static("Missing required parameters for [Route: {$route->getName()}] [URI: {$route->uri()}].");
    }
}

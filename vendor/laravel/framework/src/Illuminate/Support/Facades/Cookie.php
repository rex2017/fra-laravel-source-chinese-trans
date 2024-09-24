<?php
/**
 * 支持，门面Cookie
 */

namespace Illuminate\Support\Facades;

/**
 * @method static void queue(...$parameters)
 * @method static unqueue($name)
 * @method static array getQueuedCookies()
 *
 * @see \Illuminate\Cookie\CookieJar
 */
class Cookie extends Facade
{
    /**
     * Determine if a cookie exists on the request.
	 * 确定请求中是否存在cookie
     *
     * @param  string  $key
     * @return bool
     */
    public static function has($key)
    {
        return ! is_null(static::$app['request']->cookie($key, null));
    }

    /**
     * Retrieve a cookie from the request.
	 * 从请求中检索cookie
     *
     * @param  string|null  $key
     * @param  mixed  $default
     * @return string|array|null
     */
    public static function get($key = null, $default = null)
    {
        return static::$app['request']->cookie($key, $default);
    }

    /**
     * Get the registered name of the component.
	 * 得到组件注册名
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return 'cookie';
    }
}

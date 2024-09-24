<?php
/**
 * COOKIE值前缀
 */

namespace Illuminate\Cookie;

class CookieValuePrefix
{
    /**
     * Create a new cookie value prefix for the given cookie name.
	 * 创建新的Cookie值前缀为给定的cookie名称
     *
     * @param  string  $cookieName
     * @param  string  $key
     * @return string
     */
    public static function create($cookieName, $key)
    {
        return hash_hmac('sha1', $cookieName.'v2', $key).'|';
    }

    /**
     * Remove the cookie value prefix.
	 * 移除cookie值前缀
     *
     * @param  string  $cookieValue
     * @return string
     */
    public static function remove($cookieValue)
    {
        return substr($cookieValue, 41);
    }
}

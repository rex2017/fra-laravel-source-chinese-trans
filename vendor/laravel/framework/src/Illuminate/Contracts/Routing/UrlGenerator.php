<?php
/**
 * 契约，路由URL生成器接口
 */

namespace Illuminate\Contracts\Routing;

interface UrlGenerator
{
    /**
     * Get the current URL for the request.
	 * 得到当前请求URL
     *
     * @return string
     */
    public function current();

    /**
     * Get the URL for the previous request.
	 * 得到前一个请求的URL
     *
     * @param  mixed  $fallback
     * @return string
     */
    public function previous($fallback = false);

    /**
     * Generate an absolute URL to the given path.
	 * 生成一个真正的URL
     *
     * @param  string  $path
     * @param  mixed  $extra
     * @param  bool|null  $secure
     * @return string
     */
    public function to($path, $extra = [], $secure = null);

    /**
     * Generate a secure, absolute URL to the given path.
	 * 生成给定路径的安全的绝对URL
     *
     * @param  string  $path
     * @param  array  $parameters
     * @return string
     */
    public function secure($path, $parameters = []);

    /**
     * Generate the URL to an application asset.
	 * 生成应用程序资产的URL
     *
     * @param  string  $path
     * @param  bool|null  $secure
     * @return string
     */
    public function asset($path, $secure = null);

    /**
     * Get the URL to a named route.
	 * 得到命名路由的URL
     *
     * @param  string  $name
     * @param  mixed  $parameters
     * @param  bool  $absolute
     * @return string
     *
     * @throws \InvalidArgumentException
     */
    public function route($name, $parameters = [], $absolute = true);

    /**
     * Get the URL to a controller action.
	 * 得到控制器动作的URL
     *
     * @param  string|array  $action
     * @param  mixed  $parameters
     * @param  bool  $absolute
     * @return string
     */
    public function action($action, $parameters = [], $absolute = true);

    /**
     * Set the root controller namespace.
	 * 设置根控制器命名空间
     *
     * @param  string  $rootNamespace
     * @return $this
     */
    public function setRootControllerNamespace($rootNamespace);
}

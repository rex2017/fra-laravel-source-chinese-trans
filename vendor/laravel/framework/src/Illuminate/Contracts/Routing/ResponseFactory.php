<?php
/**
 * 契约，路由响应工厂接口
 */

namespace Illuminate\Contracts\Routing;

interface ResponseFactory
{
    /**
     * Create a new response instance.
	 * 创建一个新的响应实例
     *
     * @param  string  $content
     * @param  int  $status
     * @param  array  $headers
     * @return \Illuminate\Http\Response
     */
    public function make($content = '', $status = 200, array $headers = []);

    /**
     * Create a new "no content" response.
	 * 创建新的"无内容"响应
     *
     * @param  int  $status
     * @param  array  $headers
     * @return \Illuminate\Http\Response
     */
    public function noContent($status = 204, array $headers = []);

    /**
     * Create a new response for a given view.
	 * 创建新的响应为给定视图
     *
     * @param  string|array  $view
     * @param  array  $data
     * @param  int  $status
     * @param  array  $headers
     * @return \Illuminate\Http\Response
     */
    public function view($view, $data = [], $status = 200, array $headers = []);

    /**
     * Create a new JSON response instance.
	 * 创建新的JSON响应实例
     *
     * @param  string|array|object  $data
     * @param  int  $status
     * @param  array  $headers
     * @param  int  $options
     * @return \Illuminate\Http\JsonResponse
     */
    public function json($data = [], $status = 200, array $headers = [], $options = 0);

    /**
     * Create a new JSONP response instance.
	 * 创建新的JSONP响应实例
     *
     * @param  string  $callback
     * @param  string|array|object  $data
     * @param  int  $status
     * @param  array  $headers
     * @param  int  $options
     * @return \Illuminate\Http\JsonResponse
     */
    public function jsonp($callback, $data = [], $status = 200, array $headers = [], $options = 0);

    /**
     * Create a new streamed response instance.
	 * 创建新的流响应实例
     *
     * @param  \Closure  $callback
     * @param  int  $status
     * @param  array  $headers
     * @return \Symfony\Component\HttpFoundation\StreamedResponse
     */
    public function stream($callback, $status = 200, array $headers = []);

    /**
     * Create a new streamed response instance as a file download.
	 * 创建新的流响应实例作为文件下载
     *
     * @param  \Closure  $callback
     * @param  string|null  $name
     * @param  array  $headers
     * @param  string|null  $disposition
     * @return \Symfony\Component\HttpFoundation\StreamedResponse
     */
    public function streamDownload($callback, $name = null, array $headers = [], $disposition = 'attachment');

    /**
     * Create a new file download response.
	 * 创建新的文件下载响应
     *
     * @param  \SplFileInfo|string  $file
     * @param  string|null  $name
     * @param  array  $headers
     * @param  string|null  $disposition
     * @return \Symfony\Component\HttpFoundation\BinaryFileResponse
     */
    public function download($file, $name = null, array $headers = [], $disposition = 'attachment');

    /**
     * Return the raw contents of a binary file.
	 * 返回二进制文件的原始内容
     *
     * @param  \SplFileInfo|string  $file
     * @param  array  $headers
     * @return \Symfony\Component\HttpFoundation\BinaryFileResponse
     */
    public function file($file, array $headers = []);

    /**
     * Create a new redirect response to the given path.
	 * 创建新的跳转响应
     *
     * @param  string  $path
     * @param  int  $status
     * @param  array  $headers
     * @param  bool|null  $secure
     * @return \Illuminate\Http\RedirectResponse
     */
    public function redirectTo($path, $status = 302, $headers = [], $secure = null);

    /**
     * Create a new redirect response to a named route.
	 * 创建一个新的重定向响应给路由
     *
     * @param  string  $route
     * @param  array  $parameters
     * @param  int  $status
     * @param  array  $headers
     * @return \Illuminate\Http\RedirectResponse
     */
    public function redirectToRoute($route, $parameters = [], $status = 302, $headers = []);

    /**
     * Create a new redirect response to a controller action.
	 * 创建一个新的重定向响应给控制器动作
     *
     * @param  string  $action
     * @param  array  $parameters
     * @param  int  $status
     * @param  array  $headers
     * @return \Illuminate\Http\RedirectResponse
     */
    public function redirectToAction($action, $parameters = [], $status = 302, $headers = []);

    /**
     * Create a new redirect response, while putting the current URL in the session.
	 * 创建一个新的重定向响应，同时将当前URL放在会话中。
     *
     * @param  string  $path
     * @param  int  $status
     * @param  array  $headers
     * @param  bool|null  $secure
     * @return \Illuminate\Http\RedirectResponse
     */
    public function redirectGuest($path, $status = 302, $headers = [], $secure = null);

    /**
     * Create a new redirect response to the previously intended location.
	 * 创建到先前预期位置的新重定向响应
     *
     * @param  string  $default
     * @param  int  $status
     * @param  array  $headers
     * @param  bool|null  $secure
     * @return \Illuminate\Http\RedirectResponse
     */
    public function redirectToIntended($default = '/', $status = 302, $headers = [], $secure = null);
}

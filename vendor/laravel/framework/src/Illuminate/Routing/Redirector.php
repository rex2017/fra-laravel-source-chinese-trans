<?php
/**
 * 路由重定向
 */

namespace Illuminate\Routing;

use Illuminate\Http\RedirectResponse;
use Illuminate\Session\Store as SessionStore;
use Illuminate\Support\Traits\Macroable;

class Redirector
{
    use Macroable;

    /**
     * The URL generator instance.
	 * URL生成器实例
     *
     * @var \Illuminate\Routing\UrlGenerator
     */
    protected $generator;

    /**
     * The session store instance.
	 * session存储实例
     *
     * @var \Illuminate\Session\Store
     */
    protected $session;

    /**
     * Create a new Redirector instance.
	 * 创建新的重定向实例
     *
     * @param  \Illuminate\Routing\UrlGenerator  $generator
     * @return void
     */
    public function __construct(UrlGenerator $generator)
    {
        $this->generator = $generator;
    }

    /**
     * Create a new redirect response to the "home" route.
	 * 创建一个指向home路由的新重定向响应
     *
     * @param  int  $status
     * @return \Illuminate\Http\RedirectResponse
     */
    public function home($status = 302)
    {
        return $this->to($this->generator->route('home'), $status);
    }

    /**
     * Create a new redirect response to the previous location.
	 * 创建新的跳转响应至前一个位置
     *
     * @param  int  $status
     * @param  array  $headers
     * @param  mixed  $fallback
     * @return \Illuminate\Http\RedirectResponse
     */
    public function back($status = 302, $headers = [], $fallback = false)
    {
        return $this->createRedirect($this->generator->previous($fallback), $status, $headers);
    }

    /**
     * Create a new redirect response to the current URI.
	 * 创建新的跳转响应至当前URI
     *
     * @param  int  $status
     * @param  array  $headers
     * @return \Illuminate\Http\RedirectResponse
     */
    public function refresh($status = 302, $headers = [])
    {
        return $this->to($this->generator->getRequest()->path(), $status, $headers);
    }

    /**
     * Create a new redirect response, while putting the current URL in the session.
	 * 创建新的重定向响应，同时将当前URL放在会话中
     *
     * @param  string  $path
     * @param  int  $status
     * @param  array  $headers
     * @param  bool|null  $secure
     * @return \Illuminate\Http\RedirectResponse
     */
    public function guest($path, $status = 302, $headers = [], $secure = null)
    {
        $request = $this->generator->getRequest();

        $intended = $request->method() === 'GET' && $request->route() && ! $request->expectsJson()
                        ? $this->generator->full()
                        : $this->generator->previous();

        if ($intended) {
            $this->setIntendedUrl($intended);
        }

        return $this->to($path, $status, $headers, $secure);
    }

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
    public function intended($default = '/', $status = 302, $headers = [], $secure = null)
    {
        $path = $this->session->pull('url.intended', $default);

        return $this->to($path, $status, $headers, $secure);
    }

    /**
     * Set the intended url.
	 * 设置预期的url
     *
     * @param  string  $url
     * @return void
     */
    public function setIntendedUrl($url)
    {
        $this->session->put('url.intended', $url);
    }

    /**
     * Create a new redirect response to the given path.
	 * 创建对给定路径的新重定向响应
     *
     * @param  string  $path
     * @param  int  $status
     * @param  array  $headers
     * @param  bool|null  $secure
     * @return \Illuminate\Http\RedirectResponse
     */
    public function to($path, $status = 302, $headers = [], $secure = null)
    {
        return $this->createRedirect($this->generator->to($path, [], $secure), $status, $headers);
    }

    /**
     * Create a new redirect response to an external URL (no validation).
	 * 创建一个指向外部URL的新重定向响应(不需要验证)
     *
     * @param  string  $path
     * @param  int  $status
     * @param  array  $headers
     * @return \Illuminate\Http\RedirectResponse
     */
    public function away($path, $status = 302, $headers = [])
    {
        return $this->createRedirect($path, $status, $headers);
    }

    /**
     * Create a new redirect response to the given HTTPS path.
	 * 创建新的重定向响应为给定的HTTPS路径
     *
     * @param  string  $path
     * @param  int  $status
     * @param  array  $headers
     * @return \Illuminate\Http\RedirectResponse
     */
    public function secure($path, $status = 302, $headers = [])
    {
        return $this->to($path, $status, $headers, true);
    }

    /**
     * Create a new redirect response to a named route.
	 * 创建新的重定向响应为路由
     *
     * @param  string  $route
     * @param  mixed  $parameters
     * @param  int  $status
     * @param  array  $headers
     * @return \Illuminate\Http\RedirectResponse
     */
    public function route($route, $parameters = [], $status = 302, $headers = [])
    {
        return $this->to($this->generator->route($route, $parameters), $status, $headers);
    }

    /**
     * Create a new redirect response to a controller action.
	 * 创建新的重定向响应为控制器动作
     *
     * @param  string|array  $action
     * @param  mixed  $parameters
     * @param  int  $status
     * @param  array  $headers
     * @return \Illuminate\Http\RedirectResponse
     */
    public function action($action, $parameters = [], $status = 302, $headers = [])
    {
        return $this->to($this->generator->action($action, $parameters), $status, $headers);
    }

    /**
     * Create a new redirect response.
	 * 创建新的跳转响应
     *
     * @param  string  $path
     * @param  int  $status
     * @param  array  $headers
     * @return \Illuminate\Http\RedirectResponse
     */
    protected function createRedirect($path, $status, $headers)
    {
        return tap(new RedirectResponse($path, $status, $headers), function ($redirect) {
            if (isset($this->session)) {
                $redirect->setSession($this->session);
            }

            $redirect->setRequest($this->generator->getRequest());
        });
    }

    /**
     * Get the URL generator instance.
	 * 得到URL生成器实例
     *
     * @return \Illuminate\Routing\UrlGenerator
     */
    public function getUrlGenerator()
    {
        return $this->generator;
    }

    /**
     * Set the active session store.
	 * 设置活动会话
     *
     * @param  \Illuminate\Session\Store  $session
     * @return void
     */
    public function setSession(SessionStore $session)
    {
        $this->session = $session;
    }
}

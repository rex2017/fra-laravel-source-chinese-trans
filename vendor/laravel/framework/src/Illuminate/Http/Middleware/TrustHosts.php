<?php
/**
 * Http，真实主机
 */

namespace Illuminate\Http\Middleware;

use Illuminate\Contracts\Foundation\Application;
use Illuminate\Http\Request;

abstract class TrustHosts
{
    /**
     * The application instance.
	 * 应用实例
     *
     * @var \Illuminate\Contracts\Foundation\Application
     */
    protected $app;

    /**
     * Create a new middleware instance.
	 * 创建新的中间件实例
     *
     * @param  \Illuminate\Contracts\Foundation\Application  $app
     * @return void
     */
    public function __construct(Application $app)
    {
        $this->app = $app;
    }

    /**
     * Get the host patterns that should be trusted.
	 * 得到应该被信任的主机模式
     *
     * @return array
     */
    abstract public function hosts();

    /**
     * Handle the incoming request.
	 * 处理传入请求
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  callable  $next
     * @return \Illuminate\Http\Response
     */
    public function handle(Request $request, $next)
    {
        if ($this->shouldSpecifyTrustedHosts()) {
            Request::setTrustedHosts(array_filter($this->hosts()));
        }

        return $next($request);
    }

    /**
     * Determine if the application should specify trusted hosts.
	 * 确定应用程序是否应该指定受信任的主机
     *
     * @return bool
     */
    protected function shouldSpecifyTrustedHosts()
    {
        return config('app.env') !== 'local' &&
               ! $this->app->runningUnitTests();
    }

    /**
     * Get a regular expression matching the application URL and all of its subdomains.
	 * 得到匹配应用程序URL及其所有子域的正则表达式
     *
     * @return string|null
     */
    protected function allSubdomainsOfApplicationUrl()
    {
        if ($host = parse_url($this->app['config']->get('app.url'), PHP_URL_HOST)) {
            return '^(.+\.)?'.preg_quote($host).'$';
        }
    }
}

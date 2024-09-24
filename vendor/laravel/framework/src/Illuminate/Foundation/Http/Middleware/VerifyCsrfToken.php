<?php
/**
 * 基础，Http中间件，验证令牌
 */

namespace Illuminate\Foundation\Http\Middleware;

use Closure;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Contracts\Encryption\Encrypter;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\Support\Responsable;
use Illuminate\Cookie\CookieValuePrefix;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Session\TokenMismatchException;
use Illuminate\Support\InteractsWithTime;
use Symfony\Component\HttpFoundation\Cookie;

class VerifyCsrfToken
{
    use InteractsWithTime;

    /**
     * The application instance.
	 * 应用实例
     *
     * @var \Illuminate\Contracts\Foundation\Application
     */
    protected $app;

    /**
     * The encrypter implementation.
	 * 加密器实现
     *
     * @var \Illuminate\Contracts\Encryption\Encrypter
     */
    protected $encrypter;

    /**
     * The URIs that should be excluded from CSRF verification.
	 * 应该从CSRF验证中排除的URI
     *
     * @var array
     */
    protected $except = [];

    /**
     * Indicates whether the XSRF-TOKEN cookie should be set on the response.
	 * 指明是否应该在响应上设置XSRF-TOKEN cookie
     *
     * @var bool
     */
    protected $addHttpCookie = true;

    /**
     * Create a new middleware instance.
	 * 创建新的中间件实例
     *
     * @param  \Illuminate\Contracts\Foundation\Application  $app
     * @param  \Illuminate\Contracts\Encryption\Encrypter  $encrypter
     * @return void
     */
    public function __construct(Application $app, Encrypter $encrypter)
    {
        $this->app = $app;
        $this->encrypter = $encrypter;
    }

    /**
     * Handle an incoming request.
	 * 处理传入请求
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     *
     * @throws \Illuminate\Session\TokenMismatchException
     */
    public function handle($request, Closure $next)
    {
        if (
            $this->isReading($request) ||
            $this->runningUnitTests() ||
            $this->inExceptArray($request) ||
            $this->tokensMatch($request)
        ) {
            return tap($next($request), function ($response) use ($request) {
                if ($this->shouldAddXsrfTokenCookie()) {
                    $this->addCookieToResponse($request, $response);
                }
            });
        }

        throw new TokenMismatchException('CSRF token mismatch.');
    }

    /**
     * Determine if the HTTP request uses a ‘read’ verb.
	 * 确定HTTP请求是否使用read
     *
     * @param  \Illuminate\Http\Request  $request
     * @return bool
     */
    protected function isReading($request)
    {
        return in_array($request->method(), ['HEAD', 'GET', 'OPTIONS']);
    }

    /**
     * Determine if the application is running unit tests.
	 * 确定应用程序是否正在运行单元测试
     *
     * @return bool
     */
    protected function runningUnitTests()
    {
        return $this->app->runningInConsole() && $this->app->runningUnitTests();
    }

    /**
     * Determine if the request has a URI that should pass through CSRF verification.
	 * 确定请求是否具有应该通过CSRF验证的URI
     *
     * @param  \Illuminate\Http\Request  $request
     * @return bool
     */
    protected function inExceptArray($request)
    {
        foreach ($this->except as $except) {
            if ($except !== '/') {
                $except = trim($except, '/');
            }

            if ($request->fullUrlIs($except) || $request->is($except)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Determine if the session and input CSRF tokens match.
	 * 确定会话和输入CSRF令牌是否匹配
     *
     * @param  \Illuminate\Http\Request  $request
     * @return bool
     */
    protected function tokensMatch($request)
    {
        $token = $this->getTokenFromRequest($request);

        return is_string($request->session()->token()) &&
               is_string($token) &&
               hash_equals($request->session()->token(), $token);
    }

    /**
     * Get the CSRF token from the request.
	 * 得到CSRF令牌从请求中
     *
     * @param  \Illuminate\Http\Request  $request
     * @return string
     */
    protected function getTokenFromRequest($request)
    {
        $token = $request->input('_token') ?: $request->header('X-CSRF-TOKEN');

        if (! $token && $header = $request->header('X-XSRF-TOKEN')) {
            try {
                $token = CookieValuePrefix::remove($this->encrypter->decrypt($header, static::serialized()));
            } catch (DecryptException $e) {
                $token = '';
            }
        }

        return $token;
    }

    /**
     * Determine if the cookie should be added to the response.
	 * 确定是否应该将cookie添加到响应中
     *
     * @return bool
     */
    public function shouldAddXsrfTokenCookie()
    {
        return $this->addHttpCookie;
    }

    /**
     * Add the CSRF token to the response cookies.
	 * 添加CSRF令牌至响应cookie中
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Symfony\Component\HttpFoundation\Response  $response
     * @return \Symfony\Component\HttpFoundation\Response
     */
    protected function addCookieToResponse($request, $response)
    {
        $config = config('session');

        if ($response instanceof Responsable) {
            $response = $response->toResponse($request);
        }

        $response->headers->setCookie(
            new Cookie(
                'XSRF-TOKEN', $request->session()->token(), $this->availableAt(60 * $config['lifetime']),
                $config['path'], $config['domain'], $config['secure'], false, false, $config['same_site'] ?? null
            )
        );

        return $response;
    }

    /**
     * Determine if the cookie contents should be serialized.
	 * 确定是否应该序列化cookie内容
     *
     * @return bool
     */
    public static function serialized()
    {
        return EncryptCookies::serialized('XSRF-TOKEN');
    }
}

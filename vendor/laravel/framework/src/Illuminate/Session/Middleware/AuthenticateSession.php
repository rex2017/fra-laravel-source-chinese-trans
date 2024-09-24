<?php
/**
 * 会话，身份验证会话
 */

namespace Illuminate\Session\Middleware;

use Closure;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Contracts\Auth\Factory as AuthFactory;

class AuthenticateSession
{
    /**
     * The authentication factory implementation.
	 * 身份验证工厂实现
     *
     * @var \Illuminate\Contracts\Auth\Factory
     */
    protected $auth;

    /**
     * Create a new middleware instance.
	 * 创建新的中间件实例
     *
     * @param  \Illuminate\Contracts\Auth\Factory  $auth
     * @return void
     */
    public function __construct(AuthFactory $auth)
    {
        $this->auth = $auth;
    }

    /**
     * Handle an incoming request.
	 * 处理传入请求
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        if (! $request->hasSession() || ! $request->user()) {
            return $next($request);
        }

        if ($this->auth->viaRemember()) {
            $passwordHash = explode('|', $request->cookies->get($this->auth->getRecallerName()))[2] ?? null;

            if (! $passwordHash || $passwordHash != $request->user()->getAuthPassword()) {
                $this->logout($request);
            }
        }

        if (! $request->session()->has('password_hash')) {
            $this->storePasswordHashInSession($request);
        }

        if ($request->session()->get('password_hash') !== $request->user()->getAuthPassword()) {
            $this->logout($request);
        }

        return tap($next($request), function () use ($request) {
            $this->storePasswordHashInSession($request);
        });
    }

    /**
     * Store the user's current password hash in the session.
	 * 散列用户的当前密码存储在会话中
     *
     * @param  \Illuminate\Http\Request  $request
     * @return void
     */
    protected function storePasswordHashInSession($request)
    {
        if (! $request->user()) {
            return;
        }

        $request->session()->put([
            'password_hash' => $request->user()->getAuthPassword(),
        ]);
    }

    /**
     * Log the user out of the application.
	 * 注销用户从应用程序中
     *
     * @param  \Illuminate\Http\Request  $request
     * @return void
     *
     * @throws \Illuminate\Auth\AuthenticationException
     */
    protected function logout($request)
    {
        $this->auth->logoutCurrentDevice();

        $request->session()->flush();

        throw new AuthenticationException;
    }
}

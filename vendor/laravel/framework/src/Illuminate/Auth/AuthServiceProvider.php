<?php
/**
 * Auth服务提供者
 */

namespace Illuminate\Auth;

use Illuminate\Auth\Access\Gate;
use Illuminate\Auth\Middleware\RequirePassword;
use Illuminate\Contracts\Auth\Access\Gate as GateContract;
use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;
use Illuminate\Contracts\Routing\ResponseFactory;
use Illuminate\Contracts\Routing\UrlGenerator;
use Illuminate\Support\ServiceProvider;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * Register the service provider.
	 * 已注册服务提供者
     *
     * @return void
     */
    public function register()
    {
        $this->registerAuthenticator();
        $this->registerUserResolver();
        $this->registerAccessGate();
        $this->registerRequirePassword();
        $this->registerRequestRebindHandler();
        $this->registerEventRebindHandler();
    }

    /**
     * Register the authenticator services.
	 * 注册验证器服务
     *
     * @return void
     */
    protected function registerAuthenticator()
    {
        $this->app->singleton('auth', function ($app) {
            // Once the authentication service has actually been requested by the developer
            // we will set a variable in the application indicating such. This helps us
            // know that we need to set any queued cookies in the after event later.
			// 一旦开发人员实际请求了身份验证服务，我们将在应用程序中设置一个变量来指示这一点。
			// 这有助于我们知道稍后需要在事后事件中设置任何排队的Cookie。
            $app['auth.loaded'] = true;

            return new AuthManager($app);
        });

        $this->app->singleton('auth.driver', function ($app) {
            return $app['auth']->guard();
        });
    }

    /**
     * Register a resolver for the authenticated user.
	 * 注册一个解析器为经过身份验证的用户
     *
     * @return void
     */
    protected function registerUserResolver()
    {
        $this->app->bind(
            AuthenticatableContract::class, function ($app) {
                return call_user_func($app['auth']->userResolver());
            }
        );
    }

    /**
     * Register the access gate service.
	 * 注册门禁服务
     *
     * @return void
     */
    protected function registerAccessGate()
    {
        $this->app->singleton(GateContract::class, function ($app) {
            return new Gate($app, function () use ($app) {
                return call_user_func($app['auth']->userResolver());
            });
        });
    }

    /**
     * Register a resolver for the authenticated user.
	 * 注册一个解析器为经过身份验证的用户
     *
     * @return void
     */
    protected function registerRequirePassword()
    {
        $this->app->bind(
            RequirePassword::class, function ($app) {
                return new RequirePassword(
                    $app[ResponseFactory::class],
                    $app[UrlGenerator::class],
                    $app['config']->get('auth.password_timeout')
                );
            }
        );
    }

    /**
     * Handle the re-binding of the request binding.
	 * 处理请求绑定的重新绑定
     *
     * @return void
     */
    protected function registerRequestRebindHandler()
    {
        $this->app->rebinding('request', function ($app, $request) {
            $request->setUserResolver(function ($guard = null) use ($app) {
                return call_user_func($app['auth']->userResolver(), $guard);
            });
        });
    }

    /**
     * Handle the re-binding of the event dispatcher binding.
	 * 处理事件调度程序绑定的重新绑定
     *
     * @return void
     */
    protected function registerEventRebindHandler()
    {
        $this->app->rebinding('events', function ($app, $dispatcher) {
            if (! $app->resolved('auth')) {
                return;
            }

            if ($app['auth']->hasResolvedGuards() === false) {
                return;
            }

            if (method_exists($guard = $app['auth']->guard(), 'setDispatcher')) {
                $guard->setDispatcher($dispatcher);
            }
        });
    }
}

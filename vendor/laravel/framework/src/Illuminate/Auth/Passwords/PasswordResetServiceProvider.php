<?php
/**
 * 身份，密码重置服务提供者
 */

namespace Illuminate\Auth\Passwords;

use Illuminate\Contracts\Support\DeferrableProvider;
use Illuminate\Support\ServiceProvider;

class PasswordResetServiceProvider extends ServiceProvider implements DeferrableProvider
{
    /**
     * Register the service provider.
	 * 注册服务提供者
     *
     * @return void
     */
    public function register()
    {
        $this->registerPasswordBroker();
    }

    /**
     * Register the password broker instance.
	 * 注册密码破解实例
     *
     * @return void
     */
    protected function registerPasswordBroker()
    {
        $this->app->singleton('auth.password', function ($app) {
            return new PasswordBrokerManager($app);
        });

        $this->app->bind('auth.password.broker', function ($app) {
            return $app->make('auth.password')->broker();
        });
    }

    /**
     * Get the services provided by the provider.
	 * 得到提供者提供的服务
     *
     * @return array
     */
    public function provides()
    {
        return ['auth.password', 'auth.password.broker'];
    }
}

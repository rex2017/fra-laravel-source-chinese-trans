<?php
/**
 * Session服务提供者类
 */

namespace Illuminate\Session;

use Illuminate\Session\Middleware\StartSession;
use Illuminate\Support\ServiceProvider;

class SessionServiceProvider extends ServiceProvider
{
    /**
     * Register the service provider.
	 * 注册服务提供者
     *
     * @return void
     */
    public function register()
    {
        $this->registerSessionManager();

        $this->registerSessionDriver();

        $this->app->singleton(StartSession::class);
    }

    /**
     * Register the session manager instance.
	 * 注册会话管理器实例
     *
     * @return void
     */
    protected function registerSessionManager()
    {
        $this->app->singleton('session', function ($app) {
            return new SessionManager($app);
        });
    }

    /**
     * Register the session driver instance.
	 * 注册会话驱动程序实例
     *
     * @return void
     */
    protected function registerSessionDriver()
    {
        $this->app->singleton('session.store', function ($app) {
            // First, we will create the session manager which is responsible for the
            // creation of the various session drivers when they are needed by the
            // application instance, and will resolve them on a lazy load basis.
			// 首先，我们将创建会话管理器，该管理器负责在应用程序实例需要时创建各种会话驱动程序，
			// 并在延迟加载的基础上解析它们。
            return $app->make('session')->driver();
        });
    }
}

<?php
/**
 * 通知服务提供者
 */

namespace Illuminate\Notifications;

use Illuminate\Contracts\Notifications\Dispatcher as DispatcherContract;
use Illuminate\Contracts\Notifications\Factory as FactoryContract;
use Illuminate\Support\ServiceProvider;

class NotificationServiceProvider extends ServiceProvider
{
    /**
     * Boot the application services.
	 * 引导应用服务
     *
     * @return void
     */
    public function boot()
    {
        $this->loadViewsFrom(__DIR__.'/resources/views', 'notifications');

        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/resources/views' => $this->app->resourcePath('views/vendor/notifications'),
            ], 'laravel-notifications');
        }
    }

    /**
     * Register the service provider.
	 * 注册服务提供者
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton(ChannelManager::class, function ($app) {
            return new ChannelManager($app);
        });

        $this->app->alias(
            ChannelManager::class, DispatcherContract::class
        );

        $this->app->alias(
            ChannelManager::class, FactoryContract::class
        );
    }
}

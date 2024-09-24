<?php
/**
 * 广播服务提供者
 */

namespace Illuminate\Broadcasting;

use Illuminate\Contracts\Broadcasting\Broadcaster as BroadcasterContract;
use Illuminate\Contracts\Broadcasting\Factory as BroadcastingFactory;
use Illuminate\Contracts\Support\DeferrableProvider;
use Illuminate\Support\ServiceProvider;

class BroadcastServiceProvider extends ServiceProvider implements DeferrableProvider
{
    /**
     * Register the service provider.
	 * 注册服务提供者
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton(BroadcastManager::class, function ($app) {
            return new BroadcastManager($app);
        });

        $this->app->singleton(BroadcasterContract::class, function ($app) {
            return $app->make(BroadcastManager::class)->connection();
        });

        $this->app->alias(
            BroadcastManager::class, BroadcastingFactory::class
        );
    }

    /**
     * Get the services provided by the provider.
	 * 得到服务提供者
     *
     * @return array
     */
    public function provides()
    {
        return [
            BroadcastManager::class,
            BroadcastingFactory::class,
            BroadcasterContract::class,
        ];
    }
}

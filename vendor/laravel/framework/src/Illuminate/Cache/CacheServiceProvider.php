<?php
/**
 * 缓存服务提供者
 */

namespace Illuminate\Cache;

use Illuminate\Contracts\Support\DeferrableProvider;
use Illuminate\Support\ServiceProvider;
use Symfony\Component\Cache\Adapter\Psr16Adapter;

class CacheServiceProvider extends ServiceProvider implements DeferrableProvider
{
    /**
     * Register the service provider.
	 * 注册服务提供者
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton('cache', function ($app) {
            return new CacheManager($app);
        });

        $this->app->singleton('cache.store', function ($app) {
            return $app['cache']->driver();
        });

        $this->app->singleton('cache.psr6', function ($app) {
            return new Psr16Adapter($app['cache.store']);
        });

        $this->app->singleton('memcached.connector', function () {
            return new MemcachedConnector;
        });
    }

    /**
     * Get the services provided by the provider.
	 * 得到已提供服务通过提供者
     *
     * @return array
     */
    public function provides()
    {
        return [
            'cache', 'cache.store', 'cache.psr6', 'memcached.connector',
        ];
    }
}

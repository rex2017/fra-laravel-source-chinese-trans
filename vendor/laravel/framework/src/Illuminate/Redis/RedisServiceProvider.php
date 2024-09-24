<?php
/**
 * Redis服务提供者
 */

namespace Illuminate\Redis;

use Illuminate\Contracts\Support\DeferrableProvider;
use Illuminate\Support\Arr;
use Illuminate\Support\ServiceProvider;

class RedisServiceProvider extends ServiceProvider implements DeferrableProvider
{
    /**
     * Register the service provider.
	 * 注册服务提供者
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton('redis', function ($app) {
            $config = $app->make('config')->get('database.redis', []);

            return new RedisManager($app, Arr::pull($config, 'client', 'phpredis'), $config);
        });

        $this->app->bind('redis.connection', function ($app) {
            return $app['redis']->connection();
        });
    }

    /**
     * Get the services provided by the provider.
	 * 得到提供者的服务通过提供者
     *
     * @return array
     */
    public function provides()
    {
        return ['redis', 'redis.connection'];
    }
}

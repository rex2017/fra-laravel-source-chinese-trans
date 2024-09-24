<?php
/**
 * 支持，聚合服务提供商
 */

namespace Illuminate\Support;

class AggregateServiceProvider extends ServiceProvider
{
    /**
     * The provider class names.
	 * 提供者类名
     *
     * @var array
     */
    protected $providers = [];

    /**
     * An array of the service provider instances.
	 * 服务提供者实例数组
     *
     * @var array
     */
    protected $instances = [];

    /**
     * Register the service provider.
	 * 注册服务提供者
     *
     * @return void
     */
    public function register()
    {
        $this->instances = [];

        foreach ($this->providers as $provider) {
            $this->instances[] = $this->app->register($provider);
        }
    }

    /**
     * Get the services provided by the provider.
	 * 得到提供的服务通过服务提供者
     *
     * @return array
     */
    public function provides()
    {
        $provides = [];

        foreach ($this->providers as $provider) {
            $instance = $this->app->resolveProvider($provider);

            $provides = array_merge($provides, $instance->provides());
        }

        return $provides;
    }
}

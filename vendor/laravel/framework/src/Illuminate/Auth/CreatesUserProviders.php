<?php
/**
 * 创建用户服务提供者
 */

namespace Illuminate\Auth;

use InvalidArgumentException;

trait CreatesUserProviders
{
    /**
     * The registered custom provider creators.
	 * 注册的自定义提供创建者
     *
     * @var array
     */
    protected $customProviderCreators = [];

    /**
     * Create the user provider implementation for the driver.
	 * 创建用户提供程序实现为驱动程序
     *
     * @param  string|null  $provider
     * @return \Illuminate\Contracts\Auth\UserProvider|null
     *
     * @throws \InvalidArgumentException
     */
    public function createUserProvider($provider = null)
    {
        if (is_null($config = $this->getProviderConfiguration($provider))) {
            return;
        }

        if (isset($this->customProviderCreators[$driver = ($config['driver'] ?? null)])) {
            return call_user_func(
                $this->customProviderCreators[$driver], $this->app, $config
            );
        }

        switch ($driver) {
            case 'database':
                return $this->createDatabaseProvider($config);
            case 'eloquent':
                return $this->createEloquentProvider($config);
            default:
                throw new InvalidArgumentException(
                    "Authentication user provider [{$driver}] is not defined."
                );
        }
    }

    /**
     * Get the user provider configuration.
	 * 得到用户提供程序配置
     *
     * @param  string|null  $provider
     * @return array|null
     */
    protected function getProviderConfiguration($provider)
    {
        if ($provider = $provider ?: $this->getDefaultUserProvider()) {
            return $this->app['config']['auth.providers.'.$provider];
        }
    }

    /**
     * Create an instance of the database user provider.
	 * 创建数据库用户提供程序的实例
     *
     * @param  array  $config
     * @return \Illuminate\Auth\DatabaseUserProvider
     */
    protected function createDatabaseProvider($config)
    {
        $connection = $this->app['db']->connection($config['connection'] ?? null);

        return new DatabaseUserProvider($connection, $this->app['hash'], $config['table']);
    }

    /**
     * Create an instance of the Eloquent user provider.
	 * 创建Eloquent用户提供程序的实例
     *
     * @param  array  $config
     * @return \Illuminate\Auth\EloquentUserProvider
     */
    protected function createEloquentProvider($config)
    {
        return new EloquentUserProvider($this->app['hash'], $config['model']);
    }

    /**
     * Get the default user provider name.
	 * 得到默认用户提供程序名称
     *
     * @return string
     */
    public function getDefaultUserProvider()
    {
        return $this->app['config']['auth.defaults.provider'];
    }
}

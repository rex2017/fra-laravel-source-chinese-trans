<?php
/**
 * 身份验证管理器
 */

namespace Illuminate\Auth;

use Closure;
use Illuminate\Contracts\Auth\Factory as FactoryContract;
use InvalidArgumentException;

class AuthManager implements FactoryContract
{
    use CreatesUserProviders;

    /**
     * The application instance.
	 * 应用实例
     *
     * @var \Illuminate\Contracts\Foundation\Application
     */
    protected $app;

    /**
     * The registered custom driver creators.
	 * 注册的自定义驱动创建者
     *
     * @var array
     */
    protected $customCreators = [];

    /**
     * The array of created "drivers".
	 * 已创建的"驱动程序"数组
     *
     * @var array
     */
    protected $guards = [];

    /**
     * The user resolver shared by various services.
	 * 各种服务共享的用户解析器
     *
     * Determines the default user for Gate, Request, and the Authenticatable contract.
     *
     * @var \Closure
     */
    protected $userResolver;

    /**
     * Create a new Auth manager instance.
	 * 创建一个新的Auth管理器实例
     *
     * @param  \Illuminate\Contracts\Foundation\Application  $app
     * @return void
     */
    public function __construct($app)
    {
        $this->app = $app;

        $this->userResolver = function ($guard = null) {
            return $this->guard($guard)->user();
        };
    }

    /**
     * Attempt to get the guard from the local cache.
     *
     * @param  string|null  $name
     * @return \Illuminate\Contracts\Auth\Guard|\Illuminate\Contracts\Auth\StatefulGuard
     */
    public function guard($name = null)
    {
        $name = $name ?: $this->getDefaultDriver();

        return $this->guards[$name] ?? $this->guards[$name] = $this->resolve($name);
    }

    /**
     * Resolve the given guard.
	 * 解析给定的守卫
     *
     * @param  string  $name
     * @return \Illuminate\Contracts\Auth\Guard|\Illuminate\Contracts\Auth\StatefulGuard
     *
     * @throws \InvalidArgumentException
     */
    protected function resolve($name)
    {
        $config = $this->getConfig($name);

        if (is_null($config)) {
            throw new InvalidArgumentException("Auth guard [{$name}] is not defined.");
        }

        if (isset($this->customCreators[$config['driver']])) {
            return $this->callCustomCreator($name, $config);
        }

        $driverMethod = 'create'.ucfirst($config['driver']).'Driver';

        if (method_exists($this, $driverMethod)) {
            return $this->{$driverMethod}($name, $config);
        }

        throw new InvalidArgumentException(
            "Auth driver [{$config['driver']}] for guard [{$name}] is not defined."
        );
    }

    /**
     * Call a custom driver creator.
	 * 调用自定义驱动创建者
     *
     * @param  string  $name
     * @param  array  $config
     * @return mixed
     */
    protected function callCustomCreator($name, array $config)
    {
        return $this->customCreators[$config['driver']]($this->app, $name, $config);
    }

    /**
     * Create a session based authentication guard.
	 * 创建基于会话的身份验证保护
     *
     * @param  string  $name
     * @param  array  $config
     * @return \Illuminate\Auth\SessionGuard
     */
    public function createSessionDriver($name, $config)
    {
        $provider = $this->createUserProvider($config['provider'] ?? null);

        $guard = new SessionGuard($name, $provider, $this->app['session.store']);

        // When using the remember me functionality of the authentication services we
        // will need to be set the encryption instance of the guard, which allows
        // secure, encrypted cookie values to get generated for those cookies.
		// 在使用身份验证服务的记住我功能时，我们需要设置保护的加密实例，
		// 这允许为这些cookie生成安全、加密的cookie值。
        if (method_exists($guard, 'setCookieJar')) {
            $guard->setCookieJar($this->app['cookie']);
        }

        if (method_exists($guard, 'setDispatcher')) {
            $guard->setDispatcher($this->app['events']);
        }

        if (method_exists($guard, 'setRequest')) {
            $guard->setRequest($this->app->refresh('request', $guard, 'setRequest'));
        }

        return $guard;
    }

    /**
     * Create a token based authentication guard.
	 * 创建基于令牌的身份验证保护
     *
     * @param  string  $name
     * @param  array  $config
     * @return \Illuminate\Auth\TokenGuard
     */
    public function createTokenDriver($name, $config)
    {
        // The token guard implements a basic API token based guard implementation
        // that takes an API token field from the request and matches it to the
        // user in the database or another persistence layer where users are.
		// 令牌保护实现基本的基于API令牌的保护实现，该实现从请求中获取API令牌字段，
		// 并将其与数据库或用户所在的另一持久层中的用户匹配。
        $guard = new TokenGuard(
            $this->createUserProvider($config['provider'] ?? null),
            $this->app['request'],
            $config['input_key'] ?? 'api_token',
            $config['storage_key'] ?? 'api_token',
            $config['hash'] ?? false
        );

        $this->app->refresh('request', $guard, 'setRequest');

        return $guard;
    }

    /**
     * Get the guard configuration.
	 * 得到守卫配置
     *
     * @param  string  $name
     * @return array
     */
    protected function getConfig($name)
    {
        return $this->app['config']["auth.guards.{$name}"];
    }

    /**
     * Get the default authentication driver name.
     *
     * @return string
     */
    public function getDefaultDriver()
    {
        return $this->app['config']['auth.defaults.guard'];
    }

    /**
     * Set the default guard driver the factory should serve.
	 * 设置工厂应该提供的默认保护驱动程序
     *
     * @param  string  $name
     * @return void
     */
    public function shouldUse($name)
    {
        $name = $name ?: $this->getDefaultDriver();

        $this->setDefaultDriver($name);

        $this->userResolver = function ($name = null) {
            return $this->guard($name)->user();
        };
    }

    /**
     * Set the default authentication driver name.
	 * 设置默认的身份验证驱动程序名称
     *
     * @param  string  $name
     * @return void
     */
    public function setDefaultDriver($name)
    {
        $this->app['config']['auth.defaults.guard'] = $name;
    }

    /**
     * Register a new callback based request guard.
     *
     * @param  string  $driver
     * @param  callable  $callback
     * @return $this
     */
    public function viaRequest($driver, callable $callback)
    {
        return $this->extend($driver, function () use ($callback) {
            $guard = new RequestGuard($callback, $this->app['request'], $this->createUserProvider());

            $this->app->refresh('request', $guard, 'setRequest');

            return $guard;
        });
    }

    /**
     * Get the user resolver callback.
	 * 得到用户解析器回调
     *
     * @return \Closure
     */
    public function userResolver()
    {
        return $this->userResolver;
    }

    /**
     * Set the callback to be used to resolve users.
	 * 设置回调为用于解析用户
     *
     * @param  \Closure  $userResolver
     * @return $this
     */
    public function resolveUsersUsing(Closure $userResolver)
    {
        $this->userResolver = $userResolver;

        return $this;
    }

    /**
     * Register a custom driver creator Closure.
	 * 注册自定义驱动程序创建器Closure
     *
     * @param  string  $driver
     * @param  \Closure  $callback
     * @return $this
     */
    public function extend($driver, Closure $callback)
    {
        $this->customCreators[$driver] = $callback;

        return $this;
    }

    /**
     * Register a custom provider creator Closure.
	 * 注册自定义提供程序创建器闭包
     *
     * @param  string  $name
     * @param  \Closure  $callback
     * @return $this
     */
    public function provider($name, Closure $callback)
    {
        $this->customProviderCreators[$name] = $callback;

        return $this;
    }

    /**
     * Determines if any guards have already been resolved.
	 * 确定是否已经解决了任何守卫
     *
     * @return bool
     */
    public function hasResolvedGuards()
    {
        return count($this->guards) > 0;
    }

    /**
     * Dynamically call the default driver instance.
	 * 动态调用默认驱动程序实例
     *
     * @param  string  $method
     * @param  array  $parameters
     * @return mixed
     */
    public function __call($method, $parameters)
    {
        return $this->guard()->{$method}(...$parameters);
    }
}

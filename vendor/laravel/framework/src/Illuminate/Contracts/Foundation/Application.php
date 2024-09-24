<?php
/**
 * 契约，应用接口
 */

namespace Illuminate\Contracts\Foundation;

use Closure;
use Illuminate\Contracts\Container\Container;

interface Application extends Container
{
    /**
     * Get the version number of the application.
	 * 得到应用版本号
     *
     * @return string
     */
    public function version();

    /**
     * Get the base path of the Laravel installation.
	 * 得到安装的基本路径
     *
     * @param  string  $path
     * @return string
     */
    public function basePath($path = '');

    /**
     * Get the path to the bootstrap directory.
	 * 得到base下bootstrap目录
     *
     * @param  string  $path
     * @return string
     */
    public function bootstrapPath($path = '');

    /**
     * Get the path to the application configuration files.
	 * 得到base下config目录
     *
     * @param  string  $path
     * @return string
     */
    public function configPath($path = '');

    /**
     * Get the path to the database directory.
	 * 得到数据库路径
     *
     * @param  string  $path
     * @return string
     */
    public function databasePath($path = '');

    /**
     * Get the path to the environment file directory.
	 * 得到环境文件目录
     *
     * @return string
     */
    public function environmentPath();

    /**
     * Get the path to the resources directory.
	 * 得到资源目录
     *
     * @param  string  $path
     * @return string
     */
    public function resourcePath($path = '');

    /**
     * Get the path to the storage directory.
	 * 文件存储目录
     *
     * @return string
     */
    public function storagePath();

    /**
     * Get or check the current application environment.
	 * 得到或检查当前应用环境
     *
     * @param  string|array  $environments
     * @return string|bool
     */
    public function environment(...$environments);

    /**
     * Determine if the application is running in the console.
	 * 确定应用是否在控制台运行
     *
     * @return bool
     */
    public function runningInConsole();

    /**
     * Determine if the application is running unit tests.
	 * 确定应用是否在测试单元运行
     *
     * @return bool
     */
    public function runningUnitTests();

    /**
     * Determine if the application is currently down for maintenance.
	 * 确定应该否当前关闭
     *
     * @return bool
     */
    public function isDownForMaintenance();

    /**
     * Register all of the configured providers.
	 * 注册所有配置提供者
     *
     * @return void
     */
    public function registerConfiguredProviders();

    /**
     * Register a service provider with the application.
	 * 注册一个服务提供者在应用里
     *
     * @param  \Illuminate\Support\ServiceProvider|string  $provider
     * @param  bool  $force
     * @return \Illuminate\Support\ServiceProvider
     */
    public function register($provider, $force = false);

    /**
     * Register a deferred provider and service.
	 * 注册一个延迟的服务提供者
     *
     * @param  string  $provider
     * @param  string|null  $service
     * @return void
     */
    public function registerDeferredProvider($provider, $service = null);

    /**
     * Resolve a service provider instance from the class name.
	 * 解析服务提供者实例
     *
     * @param  string  $provider
     * @return \Illuminate\Support\ServiceProvider
     */
    public function resolveProvider($provider);

    /**
     * Boot the application's service providers.
	 * 启动应用服务提供者
     *
     * @return void
     */
    public function boot();

    /**
     * Register a new boot listener.
	 * 注册新的启动者
     *
     * @param  callable  $callback
     * @return void
     */
    public function booting($callback);

    /**
     * Register a new "booted" listener.
	 * 注册新的启动监听者
     *
     * @param  callable  $callback
     * @return void
     */
    public function booted($callback);

    /**
     * Run the given array of bootstrap classes.
	 * 运行给定的引导类
     *
     * @param  array  $bootstrappers
     * @return void
     */
    public function bootstrapWith(array $bootstrappers);

    /**
     * Determine if the application configuration is cached.
	 * 确定应用配置是否已缓存
     *
     * @return bool
     */
    public function configurationIsCached();

    /**
     * Detect the application's current environment.
	 * 检测应用当前环境
     *
     * @param  \Closure  $callback
     * @return string
     */
    public function detectEnvironment(Closure $callback);

    /**
     * Get the environment file the application is using.
	 * 得到应用程序正在使用的环境文件
     *
     * @return string
     */
    public function environmentFile();

    /**
     * Get the fully qualified path to the environment file.
	 * 得到环境文件的完全限定路径
     *
     * @return string
     */
    public function environmentFilePath();

    /**
     * Get the path to the configuration cache file.
	 * 得到配置缓存文件的路径
     *
     * @return string
     */
    public function getCachedConfigPath();

    /**
     * Get the path to the cached services.php file.
	 * 得到缓存服务文件路径
     *
     * @return string
     */
    public function getCachedServicesPath();

    /**
     * Get the path to the cached packages.php file.
	 * 得到缓存包文件路径
     *
     * @return string
     */
    public function getCachedPackagesPath();

    /**
     * Get the path to the routes cache file.
	 * 得到路由缓存文件路径
     *
     * @return string
     */
    public function getCachedRoutesPath();

    /**
     * Get the current application locale.
	 * 得到当前应用场所
     *
     * @return string
     */
    public function getLocale();

    /**
     * Get the application namespace.
	 * 得到应用命名空间
     *
     * @return string
     *
     * @throws \RuntimeException
     */
    public function getNamespace();

    /**
     * Get the registered service provider instances if any exist.
	 * 得到注册服务提供者实例
     *
     * @param  \Illuminate\Support\ServiceProvider|string  $provider
     * @return array
     */
    public function getProviders($provider);

    /**
     * Determine if the application has been bootstrapped before.
	 * 确定应用是否以前被引导过
     *
     * @return bool
     */
    public function hasBeenBootstrapped();

    /**
     * Load and boot all of the remaining deferred providers.
	 * 加载并启动剩余延迟提供者
     *
     * @return void
     */
    public function loadDeferredProviders();

    /**
     * Set the environment file to be loaded during bootstrapping.
	 * 设置环境文件加载在启动期间
     *
     * @param  string  $file
     * @return $this
     */
    public function loadEnvironmentFrom($file);

    /**
     * Determine if the application routes are cached.
	 * 确定应用路由是否缓存
     *
     * @return bool
     */
    public function routesAreCached();

    /**
     * Set the current application locale.
	 * 设置当前应用现场
     *
     * @param  string  $locale
     * @return void
     */
    public function setLocale($locale);

    /**
     * Determine if middleware has been disabled for the application.
	 * 确定应用是否禁用中间件
     *
     * @return bool
     */
    public function shouldSkipMiddleware();

    /**
     * Terminate the application.
	 * 终止应用
     *
     * @return void
     */
    public function terminate();
}

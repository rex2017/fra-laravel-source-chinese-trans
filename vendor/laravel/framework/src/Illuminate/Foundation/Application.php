<?php
/**
 * 基础应用类，核心类
 */

namespace Illuminate\Foundation;

use Closure;
use Illuminate\Container\Container;
use Illuminate\Contracts\Foundation\Application as ApplicationContract;
use Illuminate\Contracts\Http\Kernel as HttpKernelContract;
use Illuminate\Events\EventServiceProvider;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Foundation\Bootstrap\LoadEnvironmentVariables;
use Illuminate\Foundation\Events\LocaleUpdated;
use Illuminate\Http\Request;
use Illuminate\Log\LogServiceProvider;
use Illuminate\Routing\RoutingServiceProvider;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Env;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use RuntimeException;
use Symfony\Component\HttpFoundation\Request as SymfonyRequest;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\HttpKernelInterface;

class Application extends Container implements ApplicationContract, HttpKernelInterface
{
    /**
     * The Laravel framework version.
	 * 框架版本号
     *
     * @var string
     */
    const VERSION = '6.20.44';

    /**
     * The base path for the Laravel installation.
	 * 安装的基本路径
     *
     * @var string
     */
    protected $basePath;

    /**
     * Indicates if the application has been bootstrapped before.
	 * 指出是否应用之前已启动
     *
     * @var bool
     */
    protected $hasBeenBootstrapped = false;

    /**
     * Indicates if the application has "booted".
	 * 指出是否应用已启动
     *
     * @var bool
     */
    protected $booted = false;

    /**
     * The array of booting callbacks.
	 * 启动回调的数组
     *
     * @var callable[]
     */
    protected $bootingCallbacks = [];

    /**
     * The array of booted callbacks.
	 * 已启动回调的数组
     *
     * @var callable[]
     */
    protected $bootedCallbacks = [];

    /**
     * The array of terminating callbacks.
	 * 终止回调的数组
     *
     * @var callable[]
     */
    protected $terminatingCallbacks = [];

    /**
     * All of the registered service providers.
	 * 已注册服务提供者
     *
     * @var \Illuminate\Support\ServiceProvider[]
     */
    protected $serviceProviders = [];

    /**
     * The names of the loaded service providers.
	 * 已加载服务提供者名称
     *
     * @var array
     */
    protected $loadedProviders = [];

    /**
     * The deferred services and their providers.
	 * 延迟的服务和提供者
     *
     * @var array
     */
    protected $deferredServices = [];

    /**
     * The custom application path defined by the developer.
	 * 自定义的应用路径
     *
     * @var string
     */
    protected $appPath;

    /**
     * The custom database path defined by the developer.
	 * 自定义数据库路径
     *
     * @var string
     */
    protected $databasePath;

    /**
     * The custom storage path defined by the developer.
	 * 自定义存储路径
     *
     * @var string
     */
    protected $storagePath;

    /**
     * The custom environment path defined by the developer.
	 * 自定义环境路径
     *
     * @var string
     */
    protected $environmentPath;

    /**
     * The environment file to load during bootstrapping.
	 * 环境文件在引导过程中加载，默认为.env
     *
     * @var string
     */
    protected $environmentFile = '.env';

    /**
     * Indicates if the application is running in the console.
	 * 指示是否应用程序运行在控制台中
     *
     * @var bool|null
     */
    protected $isRunningInConsole;

    /**
     * The application namespace.
	 * 应用命名空间
     *
     * @var string
     */
    protected $namespace;

    /**
     * Create a new Illuminate application instance.
	 * 创建一个点亮实例
     *
     * @param  string|null  $basePath
     * @return void
     */
    public function __construct($basePath = null)
    {
		//准备工作，设置基础路径，即bootstrap所属目录，也是public的上一层目录
        if ($basePath) {
            $this->setBasePath($basePath);
        }

		//第1步，注册基本绑定至容器
        $this->registerBaseBindings();
		//第2步，注册所有基本服务提供者
        $this->registerBaseServiceProviders();
		//第3步，在容器中注册核心类别名
        $this->registerCoreContainerAliases();
    }

    /**
     * Get the version number of the application.
	 * 得到应用版本号
     *
     * @return string
     */
    public function version()
    {
        return static::VERSION;
    }

    /**
     * Register the basic bindings into the container.
	 * 1、注册基本绑定至容器
     *
     * @return void
     */
    protected function registerBaseBindings()
    {
		//放容器的static::$instance里
        static::setInstance($this);			

		//app应用实例放容器的instance里
        $this->instance('app', $this);		
		
		//应用实例放容器的instance里
        $this->instance(Container::class, $this);	
		//MIX放容器的bindings里
        $this->singleton(Mix::class);		

		//PackageManifest放容器的instance里
        $this->instance(PackageManifest::class, new PackageManifest(
            new Filesystem, $this->basePath(), $this->getCachedPackagesPath()
        ));
    }

    /**
     * Register all of the base service providers.
	 * 注册基本服务提供者
     *
     * @return void
     */
    protected function registerBaseServiceProviders()
    {
		//放应用的serviceProviders，loadedProviders，bindings里，register方法在669行
        $this->register(new EventServiceProvider($this));			#注册EventServiceProvider
        $this->register(new LogServiceProvider($this));				#注册LogServiceProvider
        $this->register(new RoutingServiceProvider($this));			#注册RoutingServiceProvider
    }

    /**
     * Run the given array of bootstrap classes.
	 * 运行引导数组类
     *
     * @param  string[]  $bootstrappers
     * @return void
     */
    public function bootstrapWith(array $bootstrappers)
    {
        $this->hasBeenBootstrapped = true;

        foreach ($bootstrappers as $bootstrapper) {
            $this['events']->dispatch('bootstrapping: '.$bootstrapper, [$this]);

            $this->make($bootstrapper)->bootstrap($this);

            $this['events']->dispatch('bootstrapped: '.$bootstrapper, [$this]);
        }
    }

    /**
     * Register a callback to run after loading the environment.
	 * 注册一个回调在加载环境之后
     *
     * @param  \Closure  $callback
     * @return void
     */
    public function afterLoadingEnvironment(Closure $callback)
    {
        $this->afterBootstrapping(
            LoadEnvironmentVariables::class, $callback
        );
    }

    /**
     * Register a callback to run before a bootstrapper.
	 * 注册一个回调在运行引导之前
     *
     * @param  string  $bootstrapper
     * @param  \Closure  $callback
     * @return void
     */
    public function beforeBootstrapping($bootstrapper, Closure $callback)
    {
        $this['events']->listen('bootstrapping: '.$bootstrapper, $callback);
    }

    /**
     * Register a callback to run after a bootstrapper.
	 * 注册一个回调在运行引导之后
     *
     * @param  string  $bootstrapper
     * @param  \Closure  $callback
     * @return void
     */
    public function afterBootstrapping($bootstrapper, Closure $callback)
    {
        $this['events']->listen('bootstrapped: '.$bootstrapper, $callback);
    }

    /**
     * Determine if the application has been bootstrapped before.
	 * 确定是否应用程序之前被引导过
     *
     * @return bool
     */
    public function hasBeenBootstrapped()
    {
        return $this->hasBeenBootstrapped;
    }

    /**
     * Set the base path for the application.
	 * 设置应用基本路径
     *
     * @param  string  $basePath
     * @return $this
     */
    public function setBasePath($basePath)
    {
        $this->basePath = rtrim($basePath, '\/');		#去掉路径右边的空格

        $this->bindPathsInContainer();

        return $this;
    }

    /**
     * Bind all of the application paths in the container.
	 * 绑定容器里所有应用程序路径
     *
     * @return void
     */
    protected function bindPathsInContainer()
    {
		//现在知道了应用下的各路径文件夹不能随便该名了的原因了吧，在这里定义了
        $this->instance('path', $this->path());						#base/app
        $this->instance('path.base', $this->basePath()); 			#base，app上一级目录
        $this->instance('path.lang', $this->langPath());			#base/lang
        $this->instance('path.config', $this->configPath());		#base/config
        $this->instance('path.public', $this->publicPath());		#base/public
        $this->instance('path.storage', $this->storagePath());		#base/storage
        $this->instance('path.database', $this->databasePath());	#base/database
        $this->instance('path.resources', $this->resourcePath());	#base/resources
        $this->instance('path.bootstrap', $this->bootstrapPath());	##base/bootstrap
    }

    /**
     * Get the path to the application "app" directory.
	 * 得到base下app目录
     *
     * @param  string  $path
     * @return string
     */
    public function path($path = '')
    {
        $appPath = $this->appPath ?: $this->basePath.DIRECTORY_SEPARATOR.'app';

        return $appPath.($path ? DIRECTORY_SEPARATOR.$path : $path);
    }

    /**
     * Set the application directory.
	 * 设置应用目录 
     *
     * @param  string  $path
     * @return $this
     */
    public function useAppPath($path)
    {
        $this->appPath = $path;

        $this->instance('path', $path);

        return $this;
    }

    /**
     * Get the base path of the Laravel installation.
	 * 得到安装的基本路径
     *
     * @param  string  $path
     * @return string
     */
    public function basePath($path = '')
    {
        return $this->basePath.($path ? DIRECTORY_SEPARATOR.$path : $path);
    }

    /**
     * Get the path to the bootstrap directory.
	 * 得到base下bootstrap目录
     *
     * @param  string  $path
     * @return string
     */
    public function bootstrapPath($path = '')
    {
        return $this->basePath.DIRECTORY_SEPARATOR.'bootstrap'.($path ? DIRECTORY_SEPARATOR.$path : $path);
    }

    /**
     * Get the path to the application configuration files.
	 * 得到base下config目录
     *
     * @param  string  $path
     * @return string
     */
    public function configPath($path = '')
    {
        return $this->basePath.DIRECTORY_SEPARATOR.'config'.($path ? DIRECTORY_SEPARATOR.$path : $path);
    }

    /**
     * Get the path to the database directory.
	 * 得到base下database目录
     *
     * @param  string  $path
     * @return string
     */
    public function databasePath($path = '')
    {
        return ($this->databasePath ?: $this->basePath.DIRECTORY_SEPARATOR.'database').($path ? DIRECTORY_SEPARATOR.$path : $path);
    }

    /**
     * Set the database directory.
	 * 设置数据库目录
     *
     * @param  string  $path
     * @return $this
     */
    public function useDatabasePath($path)
    {
        $this->databasePath = $path;

        $this->instance('path.database', $path);

        return $this;
    }

    /**
     * Get the path to the language files.
	 * 得到base下lang目录
     *
     * @return string
     */
    public function langPath()
    {
        return $this->resourcePath().DIRECTORY_SEPARATOR.'lang';
    }

    /**
     * Get the path to the public / web directory.
	 * 得到base下public目录
     *
     * @return string
     */
    public function publicPath()
    {
        return $this->basePath.DIRECTORY_SEPARATOR.'public';
    }

    /**
     * Get the path to the storage directory.
	 * 得到base下storage目录
     *
     * @return string
     */
    public function storagePath()
    {
        return $this->storagePath ?: $this->basePath.DIRECTORY_SEPARATOR.'storage';
    }

    /**
     * Set the storage directory.
	 * 设置存储目录
     *
     * @param  string  $path
     * @return $this
     */
    public function useStoragePath($path)
    {
        $this->storagePath = $path;

        $this->instance('path.storage', $path);

        return $this;
    }

    /**
     * Get the path to the resources directory.
	 * 得到base下resources目录
     *
     * @param  string  $path
     * @return string
     */
    public function resourcePath($path = '')
    {
        return $this->basePath.DIRECTORY_SEPARATOR.'resources'.($path ? DIRECTORY_SEPARATOR.$path : $path);
    }

    /**
     * Get the path to the environment file directory.
	 * 得到环境文件目录路径
     *
     * @return string
     */
    public function environmentPath()
    {
        return $this->environmentPath ?: $this->basePath;
    }

    /**
     * Set the directory for the environment file.
	 * 设置环境文件目录
     *
     * @param  string  $path
     * @return $this
     */
    public function useEnvironmentPath($path)
    {
        $this->environmentPath = $path;

        return $this;
    }

    /**
     * Set the environment file to be loaded during bootstrapping.
	 * 设置引导过程中要加载的环境文件
     *
     * @param  string  $file
     * @return $this
     */
    public function loadEnvironmentFrom($file)
    {
        $this->environmentFile = $file;

        return $this;
    }

    /**
     * Get the environment file the application is using.
	 * 得到环境文件应用正在使用的
     *
     * @return string
     */
    public function environmentFile()
    {
        return $this->environmentFile ?: '.env';
    }

    /**
     * Get the fully qualified path to the environment file.
	 * 得到环境文件的完全限定路径
     *
     * @return string
     */
    public function environmentFilePath()
    {
        return $this->environmentPath().DIRECTORY_SEPARATOR.$this->environmentFile();
    }

    /**
     * Get or check the current application environment.
	 * 得到或检查当前应用环境
     *
     * @param  string|array  $environments
     * @return string|bool
     */
    public function environment(...$environments)
    {
        if (count($environments) > 0) {
            $patterns = is_array($environments[0]) ? $environments[0] : $environments;

            return Str::is($patterns, $this['env']);
        }

        return $this['env'];
    }

    /**
     * Determine if application is in local environment.
	 * 确定是否应用本地环境
     *
     * @return bool
     */
    public function isLocal()
    {
        return $this['env'] === 'local';
    }

    /**
     * Determine if application is in production environment.
	 * 确定是否应用生产环境
     *
     * @return bool
     */
    public function isProduction()
    {
        return $this['env'] === 'production';
    }

    /**
     * Detect the application's current environment.
	 * 删除应用当前环境
     *
     * @param  \Closure  $callback
     * @return string
     */
    public function detectEnvironment(Closure $callback)
    {
        $args = $_SERVER['argv'] ?? null;

        return $this['env'] = (new EnvironmentDetector)->detect($callback, $args);
    }

    /**
     * Determine if the application is running in the console.
	 * 确定是否应用运行在控制台
     *
     * @return bool
     */
    public function runningInConsole()
    {
        if ($this->isRunningInConsole === null) {
            $this->isRunningInConsole = Env::get('APP_RUNNING_IN_CONSOLE') ?? (\PHP_SAPI === 'cli' || \PHP_SAPI === 'phpdbg');
        }

        return $this->isRunningInConsole;
    }

    /**
     * Determine if the application is running unit tests.
	 * 确定是否应用运行在单元测试
     *
     * @return bool
     */
    public function runningUnitTests()
    {
        return $this['env'] === 'testing';
    }

    /**
     * Register all of the configured providers.
	 * 注册所有配置提供者
     *
     * @return void
     */
    public function registerConfiguredProviders()
    {
        $providers = Collection::make($this->config['app.providers'])
                        ->partition(function ($provider) {
                            return strpos($provider, 'Illuminate\\') === 0;
                        });

        $providers->splice(1, 0, [$this->make(PackageManifest::class)->providers()]);

        (new ProviderRepository($this, new Filesystem, $this->getCachedServicesPath()))
                    ->load($providers->collapse()->toArray());
    }

    /**
     * Register a service provider with the application.
	 * 注册一个服务提供者到应用里
     *
     * @param  \Illuminate\Support\ServiceProvider|string  $provider
     * @param  bool  $force
     * @return \Illuminate\Support\ServiceProvider
     */
    public function register($provider, $force = false)
    {
        if (($registered = $this->getProvider($provider)) && ! $force) {
            return $registered;
        }

        // If the given "provider" is a string, we will resolve it, passing in the
        // application instance automatically for the developer. This is simply
        // a more convenient way of specifying your service provider classes.
		// 如果给定提供者是一个字符串，我们将解析它并传入应用实例。
        if (is_string($provider)) {
            $provider = $this->resolveProvider($provider);
        }

        $provider->register();

        // If there are bindings / singletons set as properties on the provider we
        // will spin through them and register them with the application, which
        // serves as a convenience layer while registering a lot of bindings.
		// 如果在提供程序上设置了绑定作为属性，我们将获取关键信息并注册到应用里。
        if (property_exists($provider, 'bindings')) {
            foreach ($provider->bindings as $key => $value) {
                $this->bind($key, $value);
            }
        }

        if (property_exists($provider, 'singletons')) {
            foreach ($provider->singletons as $key => $value) {
                $this->singleton($key, $value);
            }
        }

        $this->markAsRegistered($provider);

        // If the application has already booted, we will call this boot method on
        // the provider class so it has an opportunity to do its boot logic and
        // will be ready for any usage by this developer's application logic.
		// 如果应用已经启动，我们将调取启动方法。
        if ($this->isBooted()) {
            $this->bootProvider($provider);
        }

        return $provider;
    }

    /**
     * Get the registered service provider instance if it exists.
	 * 得到注册服务提供者实例
     *
     * @param  \Illuminate\Support\ServiceProvider|string  $provider
     * @return \Illuminate\Support\ServiceProvider|null
     */
    public function getProvider($provider)
    {
        return array_values($this->getProviders($provider))[0] ?? null;
    }

    /**
     * Get the registered service provider instances if any exist.
	 * 得到注册服务提供者实例
     *
     * @param  \Illuminate\Support\ServiceProvider|string  $provider
     * @return array
     */
    public function getProviders($provider)
    {
        $name = is_string($provider) ? $provider : get_class($provider);

        return Arr::where($this->serviceProviders, function ($value) use ($name) {
            return $value instanceof $name;
        });
    }

    /**
     * Resolve a service provider instance from the class name.
	 * 解析服务提供者实例
     *
     * @param  string  $provider
     * @return \Illuminate\Support\ServiceProvider
     */
    public function resolveProvider($provider)
    {
        return new $provider($this);
    }

    /**
     * Mark the given provider as registered.
	 * 标记为注册提供者
     *
     * @param  \Illuminate\Support\ServiceProvider  $provider
     * @return void
     */
    protected function markAsRegistered($provider)
    {
		//写入serviceProviders，loadedProviders属性
        $this->serviceProviders[] = $provider;

        $this->loadedProviders[get_class($provider)] = true;
    }

    /**
     * Load and boot all of the remaining deferred providers.
	 * 加载并引导所有剩余的延迟提供程序
     *
     * @return void
     */
    public function loadDeferredProviders()
    {
        // We will simply spin through each of the deferred providers and register each
        // one and boot them if the application has booted. This should make each of
        // the remaining services available to this application for immediate use.
		// 我们只需浏览每个延迟的提供者，注册每个提供者，并在应用程序启动后启动它们。
		// 这应该使此应用程序可以立即使用其余的每个服务。
        foreach ($this->deferredServices as $service => $provider) {
            $this->loadDeferredProvider($service);
        }

        $this->deferredServices = [];
    }

    /**
     * Load the provider for a deferred service.
	 * 加载延迟服务提供者
     *
     * @param  string  $service
     * @return void
     */
    public function loadDeferredProvider($service)
    {
        if (! $this->isDeferredService($service)) {
            return;
        }

        $provider = $this->deferredServices[$service];

        // If the service provider has not already been loaded and registered we can
        // register it with the application and remove the service from this list
        // of deferred services, since it will already be loaded on subsequent.
		// 如果服务提供商尚未加载和注册，我们可以向应用程序注册它，
		// 并从延迟服务列表中删除该服务，因为它将在后续加载。
        if (! isset($this->loadedProviders[$provider])) {
            $this->registerDeferredProvider($provider, $service);
        }
    }

    /**
     * Register a deferred provider and service.
	 * 注册延迟提供者和服务
     *
     * @param  string  $provider
     * @param  string|null  $service
     * @return void
     */
    public function registerDeferredProvider($provider, $service = null)
    {
        // Once the provider that provides the deferred service has been registered we
        // will remove it from our local list of the deferred services with related
        // providers so that this container does not try to resolve it out again.
		// 一旦注册了提供延迟服务的提供者，我们将从本地相关提供者的延迟服务列表中删除它，
		// 这样这个容器就不会再次尝试解决它。
        if ($service) {
            unset($this->deferredServices[$service]);
        }

        $this->register($instance = new $provider($this));

        if (! $this->isBooted()) {
            $this->booting(function () use ($instance) {
                $this->bootProvider($instance);
            });
        }
    }

    /**
     * Resolve the given type from the container.
	 * 从容器里解析出实例
     *
     * @param  string  $abstract
     * @param  array  $parameters
     * @return mixed
     */
    public function make($abstract, array $parameters = [])
    {
        $this->loadDeferredProviderIfNeeded($abstract = $this->getAlias($abstract));

		//容器中673行，public function make($abstract, array $parameters = [])
        return parent::make($abstract, $parameters);
    }

    /**
     * Resolve the given type from the container.
	 * 从容器里解析出实例
     *
     * @param  string  $abstract
     * @param  array  $parameters
     * @param  bool  $raiseEvents
     * @return mixed
     */
    protected function resolve($abstract, $parameters = [], $raiseEvents = true)
    {
        $this->loadDeferredProviderIfNeeded($abstract = $this->getAlias($abstract));

        return parent::resolve($abstract, $parameters, $raiseEvents);
    }

    /**
     * Load the deferred provider if the given type is a deferred service and the instance has not been loaded.
	 * 加载延迟提供程序，如果给定的类型是延迟服务并且实例尚未加载。
     *
     * @param  string  $abstract
     * @return void
     */
    protected function loadDeferredProviderIfNeeded($abstract)
    {
        if ($this->isDeferredService($abstract) && ! isset($this->instances[$abstract])) {
            $this->loadDeferredProvider($abstract);
        }
    }

    /**
     * Determine if the given abstract type has been bound.
	 * 确定给定的抽象类型是否已被绑定
     *
     * @param  string  $abstract
     * @return bool
     */
    public function bound($abstract)
    {
        return $this->isDeferredService($abstract) || parent::bound($abstract);
    }

    /**
     * Determine if the application has booted.
	 * 确定是否应用被启动
     *
     * @return bool
     */
    public function isBooted()
    {
        return $this->booted;
    }

    /**
     * Boot the application's service providers.
	 * 启动应用服务提供者
     *
     * @return void
     */
    public function boot()
    {
        if ($this->isBooted()) {
            return;
        }

        // Once the application has booted we will also fire some "booted" callbacks
        // for any listeners that need to do work after this initial booting gets
        // finished. This is useful when ordering the boot-up processes we run.
		// 一旦应用程序启动，我们还将为在初始启动完成后需要工作的任何监听器发出一些"启动"回调。
		// 在排序我们运行的启动过程时，这很有用。
        $this->fireAppCallbacks($this->bootingCallbacks);

        array_walk($this->serviceProviders, function ($p) {
            $this->bootProvider($p);
        });

        $this->booted = true;

        $this->fireAppCallbacks($this->bootedCallbacks);
    }

    /**
     * Boot the given service provider.
	 * 引导给定服务提供者
     *
     * @param  \Illuminate\Support\ServiceProvider  $provider
     * @return mixed
     */
    protected function bootProvider(ServiceProvider $provider)
    {
        if (method_exists($provider, 'boot')) {
            return $this->call([$provider, 'boot']);
        }
    }

    /**
     * Register a new boot listener.
	 * 注册一个新的启动监听者
     *
     * @param  callable  $callback
     * @return void
     */
    public function booting($callback)
    {
        $this->bootingCallbacks[] = $callback;
    }

    /**
     * Register a new "booted" listener.
	 * 注册新的已启用监听器
     *
     * @param  callable  $callback
     * @return void
     */
    public function booted($callback)
    {
        $this->bootedCallbacks[] = $callback;

        if ($this->isBooted()) {
            $this->fireAppCallbacks([$callback]);
        }
    }

    /**
     * Call the booting callbacks for the application.
	 * 调用应用程序的引导回调
     *
     * @param  callable[]  $callbacks
     * @return void
     */
    protected function fireAppCallbacks(array $callbacks)
    {
        foreach ($callbacks as $callback) {
            $callback($this);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function handle(SymfonyRequest $request, $type = self::MASTER_REQUEST, $catch = true)
    {
        return $this[HttpKernelContract::class]->handle(Request::createFromBase($request));
    }

    /**
     * Determine if middleware has been disabled for the application.
	 * 确定是否为应用程序禁用了中间件
     *
     * @return bool
     */
    public function shouldSkipMiddleware()
    {
        return $this->bound('middleware.disable') &&
               $this->make('middleware.disable') === true;
    }

    /**
     * Get the path to the cached services.php file.
	 * 得到缓存的services.php文件的路径
     *
     * @return string
     */
    public function getCachedServicesPath()
    {
        return $this->normalizeCachePath('APP_SERVICES_CACHE', 'cache/services.php');
    }

    /**
     * Get the path to the cached packages.php file.
	 * 得到缓存的packages.php文件的路径  cache/packages.php
     * 
     * @return string
     */
    public function getCachedPackagesPath()
    {
        return $this->normalizeCachePath('APP_PACKAGES_CACHE', 'cache/packages.php');
    }

    /**
     * Determine if the application configuration is cached.
	 * 确定是否缓存了应用程序配置
     *
     * @return bool
     */
    public function configurationIsCached()
    {
        return file_exists($this->getCachedConfigPath());
    }

    /**
     * Get the path to the configuration cache file.
	 * 得到缓存配置路径
     *
     * @return string
     */
    public function getCachedConfigPath()
    {
        return $this->normalizeCachePath('APP_CONFIG_CACHE', 'cache/config.php');
    }

    /**
     * Determine if the application routes are cached.
	 * 确定是否缓存了应用程序路由
     *
     * @return bool
     */
    public function routesAreCached()
    {
        return $this['files']->exists($this->getCachedRoutesPath());
    }

    /**
     * Get the path to the routes cache file.
	 * 得到路由缓存文件路径
     *
     * @return string
     */
    public function getCachedRoutesPath()
    {
        return $this->normalizeCachePath('APP_ROUTES_CACHE', 'cache/routes.php');
    }

    /**
     * Determine if the application events are cached.
	 * 确定是否缓存了应用程序事件
     *
     * @return bool
     */
    public function eventsAreCached()
    {
        return $this['files']->exists($this->getCachedEventsPath());
    }

    /**
     * Get the path to the events cache file.
	 * 得到缓存事件路径
     *
     * @return string
     */
    public function getCachedEventsPath()
    {
        return $this->normalizeCachePath('APP_EVENTS_CACHE', 'cache/events.php');
    }

    /**
     * Normalize a relative or absolute path to a cache file.
	 * 规范化缓存文件的相对或绝对路径
     *
     * @param  string  $key
     * @param  string  $default
     * @return string
     */
    protected function normalizeCachePath($key, $default)
    {
        if (is_null($env = Env::get($key))) {
            return $this->bootstrapPath($default);
        }

        return Str::startsWith($env, '/')
                ? $env
                : $this->basePath($env);
    }

    /**
     * Determine if the application is currently down for maintenance.
	 * 确定应用程序当前是否关闭以进行维护
     *
     * @return bool
     */
    public function isDownForMaintenance()
    {
        return file_exists($this->storagePath().'/framework/down');
    }

    /**
     * Throw an HttpException with the given data.
	 * 抛出一个HttpException用给定的数据
     *
     * @param  int  $code
     * @param  string  $message
     * @param  array  $headers
     * @return void
     *
     * @throws \Symfony\Component\HttpKernel\Exception\HttpException
     * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
     */
    public function abort($code, $message = '', array $headers = [])
    {
        if ($code == 404) {
            throw new NotFoundHttpException($message);
        }

        throw new HttpException($code, $message, null, $headers);
    }

    /**
     * Register a terminating callback with the application.
	 * 注册一个终止回调
     *
     * @param  callable|string  $callback
     * @return $this
     */
    public function terminating($callback)
    {
        $this->terminatingCallbacks[] = $callback;

        return $this;
    }

    /**
     * Terminate the application.
	 * 终止应用
     *
     * @return void
     */
    public function terminate()
    {
        foreach ($this->terminatingCallbacks as $terminating) {
            $this->call($terminating);
        }
    }

    /**
     * Get the service providers that have been loaded.
	 * 得到加载提供者
     *
     * @return array
     */
    public function getLoadedProviders()
    {
        return $this->loadedProviders;
    }

    /**
     * Get the application's deferred services.
	 * 得到应用程序的延迟服务
     *
     * @return array
     */
    public function getDeferredServices()
    {
        return $this->deferredServices;
    }

    /**
     * Set the application's deferred services.
	 * 设置应用程序的延迟服务
     *
     * @param  array  $services
     * @return void
     */
    public function setDeferredServices(array $services)
    {
        $this->deferredServices = $services;
    }

    /**
     * Add an array of services to the application's deferred services.
	 * 向应用程序的延迟服务添加一个服务数组
     *
     * @param  array  $services
     * @return void
     */
    public function addDeferredServices(array $services)
    {
        $this->deferredServices = array_merge($this->deferredServices, $services);
    }

    /**
     * Determine if the given service is a deferred service.
	 * 确定给定的服务是否是延迟服务
     *
     * @param  string  $service
     * @return bool
     */
    public function isDeferredService($service)
    {
        return isset($this->deferredServices[$service]);
    }

    /**
     * Configure the real-time facade namespace.
	 * 配置实时外观名称空间
     *
     * @param  string  $namespace
     * @return void
     */
    public function provideFacades($namespace)
    {
        AliasLoader::setFacadeNamespace($namespace);
    }

    /**
     * Get the current application locale.
	 * 得到当前应用本地
     *
     * @return string
     */
    public function getLocale()
    {
        return $this['config']->get('app.locale');
    }

    /**
     * Set the current application locale.
	 * 设置当前应用程序区域设置
     *
     * @param  string  $locale
     * @return void
     */
    public function setLocale($locale)
    {
        $this['config']->set('app.locale', $locale);

        $this['translator']->setLocale($locale);

        $this['events']->dispatch(new LocaleUpdated($locale));
    }

    /**
     * Determine if application locale is the given locale.
	 * 确定应用程序语言环境是否是给定的语言环境
     *
     * @param  string  $locale
     * @return bool
     */
    public function isLocale($locale)
    {
        return $this->getLocale() == $locale;
    }

    /**
     * Register the core class aliases in the container.
	 * 注册核心类映射至容器里
     *
     * @return void
     */
    public function registerCoreContainerAliases()
    {
		//写入到alias
        foreach ([
            'app'                  => [self::class, \Illuminate\Contracts\Container\Container::class, \Illuminate\Contracts\Foundation\Application::class, \Psr\Container\ContainerInterface::class],
            'auth'                 => [\Illuminate\Auth\AuthManager::class, \Illuminate\Contracts\Auth\Factory::class],
            'auth.driver'          => [\Illuminate\Contracts\Auth\Guard::class],
            'blade.compiler'       => [\Illuminate\View\Compilers\BladeCompiler::class],
            'cache'                => [\Illuminate\Cache\CacheManager::class, \Illuminate\Contracts\Cache\Factory::class],
            'cache.store'          => [\Illuminate\Cache\Repository::class, \Illuminate\Contracts\Cache\Repository::class, \Psr\SimpleCache\CacheInterface::class],
            'cache.psr6'           => [\Symfony\Component\Cache\Adapter\Psr16Adapter::class, \Symfony\Component\Cache\Adapter\AdapterInterface::class, \Psr\Cache\CacheItemPoolInterface::class],
            'config'               => [\Illuminate\Config\Repository::class, \Illuminate\Contracts\Config\Repository::class],
            'cookie'               => [\Illuminate\Cookie\CookieJar::class, \Illuminate\Contracts\Cookie\Factory::class, \Illuminate\Contracts\Cookie\QueueingFactory::class],
            'encrypter'            => [\Illuminate\Encryption\Encrypter::class, \Illuminate\Contracts\Encryption\Encrypter::class],
            'db'                   => [\Illuminate\Database\DatabaseManager::class, \Illuminate\Database\ConnectionResolverInterface::class],
            'db.connection'        => [\Illuminate\Database\Connection::class, \Illuminate\Database\ConnectionInterface::class],
            'events'               => [\Illuminate\Events\Dispatcher::class, \Illuminate\Contracts\Events\Dispatcher::class],
            'files'                => [\Illuminate\Filesystem\Filesystem::class],
            'filesystem'           => [\Illuminate\Filesystem\FilesystemManager::class, \Illuminate\Contracts\Filesystem\Factory::class],
            'filesystem.disk'      => [\Illuminate\Contracts\Filesystem\Filesystem::class],
            'filesystem.cloud'     => [\Illuminate\Contracts\Filesystem\Cloud::class],
            'hash'                 => [\Illuminate\Hashing\HashManager::class],
            'hash.driver'          => [\Illuminate\Contracts\Hashing\Hasher::class],
            'translator'           => [\Illuminate\Translation\Translator::class, \Illuminate\Contracts\Translation\Translator::class],
            'log'                  => [\Illuminate\Log\LogManager::class, \Psr\Log\LoggerInterface::class],
            'mailer'               => [\Illuminate\Mail\Mailer::class, \Illuminate\Contracts\Mail\Mailer::class, \Illuminate\Contracts\Mail\MailQueue::class],
            'auth.password'        => [\Illuminate\Auth\Passwords\PasswordBrokerManager::class, \Illuminate\Contracts\Auth\PasswordBrokerFactory::class],
            'auth.password.broker' => [\Illuminate\Auth\Passwords\PasswordBroker::class, \Illuminate\Contracts\Auth\PasswordBroker::class],
            'queue'                => [\Illuminate\Queue\QueueManager::class, \Illuminate\Contracts\Queue\Factory::class, \Illuminate\Contracts\Queue\Monitor::class],
            'queue.connection'     => [\Illuminate\Contracts\Queue\Queue::class],
            'queue.failer'         => [\Illuminate\Queue\Failed\FailedJobProviderInterface::class],
            'redirect'             => [\Illuminate\Routing\Redirector::class],
            'redis'                => [\Illuminate\Redis\RedisManager::class, \Illuminate\Contracts\Redis\Factory::class],
            'redis.connection'     => [\Illuminate\Redis\Connections\Connection::class, \Illuminate\Contracts\Redis\Connection::class],
            'request'              => [\Illuminate\Http\Request::class, \Symfony\Component\HttpFoundation\Request::class],
            'router'               => [\Illuminate\Routing\Router::class, \Illuminate\Contracts\Routing\Registrar::class, \Illuminate\Contracts\Routing\BindingRegistrar::class],
            'session'              => [\Illuminate\Session\SessionManager::class],
            'session.store'        => [\Illuminate\Session\Store::class, \Illuminate\Contracts\Session\Session::class],
            'url'                  => [\Illuminate\Routing\UrlGenerator::class, \Illuminate\Contracts\Routing\UrlGenerator::class],
            'validator'            => [\Illuminate\Validation\Factory::class, \Illuminate\Contracts\Validation\Factory::class],
            'view'                 => [\Illuminate\View\Factory::class, \Illuminate\Contracts\View\Factory::class],
        ] as $key => $aliases) {
            foreach ($aliases as $alias) {
                $this->alias($key, $alias);
            }
        }
    }

    /**
     * Flush the container of all bindings and resolved instances.
	 * 清空已绑定和已解析的实例容器
     *
     * @return void
     */
    public function flush()
    {
        parent::flush();

        $this->buildStack = [];
        $this->loadedProviders = [];
        $this->bootedCallbacks = [];
        $this->bootingCallbacks = [];
        $this->deferredServices = [];
        $this->reboundCallbacks = [];
        $this->serviceProviders = [];
        $this->resolvingCallbacks = [];
        $this->terminatingCallbacks = [];
        $this->afterResolvingCallbacks = [];
        $this->globalResolvingCallbacks = [];
    }

    /**
     * Get the application namespace.
	 * 得到命名空间
     *
     * @return string
     *
     * @throws \RuntimeException
     */
    public function getNamespace()
    {
        if (! is_null($this->namespace)) {
            return $this->namespace;
        }

        $composer = json_decode(file_get_contents($this->basePath('composer.json')), true);

        foreach ((array) data_get($composer, 'autoload.psr-4') as $namespace => $path) {
            foreach ((array) $path as $pathChoice) {
                if (realpath($this->path()) === realpath($this->basePath($pathChoice))) {
                    return $this->namespace = $namespace;
                }
            }
        }

        throw new RuntimeException('Unable to detect application namespace.');
    }
}

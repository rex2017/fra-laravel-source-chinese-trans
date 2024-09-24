<?php
/**
 * 基础，提供者资源库
 */

namespace Illuminate\Foundation;

use Exception;
use Illuminate\Contracts\Foundation\Application as ApplicationContract;
use Illuminate\Filesystem\Filesystem;

class ProviderRepository
{
    /**
     * The application implementation.
	 * 应用实现
     *
     * @var \Illuminate\Contracts\Foundation\Application
     */
    protected $app;

    /**
     * The filesystem instance.
	 * 文件系统实例
     *
     * @var \Illuminate\Filesystem\Filesystem
     */
    protected $files;

    /**
     * The path to the manifest file.
	 * 清单文件路径
     *
     * @var string
     */
    protected $manifestPath;

    /**
     * Create a new service repository instance.
	 * 创建新的服务资源实例
     *
     * @param  \Illuminate\Contracts\Foundation\Application  $app
     * @param  \Illuminate\Filesystem\Filesystem  $files
     * @param  string  $manifestPath
     * @return void
     */
    public function __construct(ApplicationContract $app, Filesystem $files, $manifestPath)
    {
        $this->app = $app;
        $this->files = $files;
        $this->manifestPath = $manifestPath;
    }

    /**
     * Register the application service providers.
	 * 注册应用服务提供者
     *
     * @param  array  $providers
     * @return void
     */
    public function load(array $providers)
    {
        $manifest = $this->loadManifest();

        // First we will load the service manifest, which contains information on all
        // service providers registered with the application and which services it
        // provides. This is used to know which services are "deferred" loaders.
		// 首先我们将加载服务清单，它将包含所有向服务提供者注册的服务提供商。
		// 这用于知道哪些服务是"延迟"加载。
        if ($this->shouldRecompile($manifest, $providers)) {
            $manifest = $this->compileManifest($providers);
        }

        // Next, we will register events to load the providers for each of the events
        // that it has requested. This allows the service provider to defer itself
        // while still getting automatically loaded when a certain event occurs.
		// 接下来，我们将注册事件以加载每个事件的提供者。
		// 这允许服务提供者延迟自己同时仍会特定事件发生时自动加载。
        foreach ($manifest['when'] as $provider => $events) {
            $this->registerLoadEvents($provider, $events);
        }

        // We will go ahead and register all of the eagerly loaded providers with the
        // application so their services can be registered with the application as
        // a provided service. Then we will set the deferred service list on it.
		// 我们将继续为所有提供商注册应用，以便他们的服务可在应用程序中注册为服务。
		// 然后，我们将在其上设置延迟服务列表。
        foreach ($manifest['eager'] as $provider) {
            $this->app->register($provider);
        }

        $this->app->addDeferredServices($manifest['deferred']);
    }

    /**
     * Load the service provider manifest JSON file.
	 * 导入服务提供者清单JSON
     *
     * @return array|null
     */
    public function loadManifest()
    {
        // The service manifest is a file containing a JSON representation of every
        // service provided by the application and whether its provider is using
        // deferred loading or should be eagerly loaded on each request to us.
		// 服务清单是一个包含的Json表示文件。
		// 无论其提供者是使用延迟加载，还是应该在每次向我们发出请求时急切地加载。
        if ($this->files->exists($this->manifestPath)) {
            $manifest = $this->files->getRequire($this->manifestPath);

            if ($manifest) {
                return array_merge(['when' => []], $manifest);
            }
        }
    }

    /**
     * Determine if the manifest should be compiled.
	 * 确定是否清单被编译
     *
     * @param  array  $manifest
     * @param  array  $providers
     * @return bool
     */
    public function shouldRecompile($manifest, $providers)
    {
        return is_null($manifest) || $manifest['providers'] != $providers;
    }

    /**
     * Register the load events for the given provider.
	 * 注册给定提供程序的加载事件
     *
     * @param  string  $provider
     * @param  array  $events
     * @return void
     */
    protected function registerLoadEvents($provider, array $events)
    {
        if (count($events) < 1) {
            return;
        }

        $this->app->make('events')->listen($events, function () use ($provider) {
            $this->app->register($provider);
        });
    }

    /**
     * Compile the application service manifest file.
	 * 编译应用程序服务清单文件
     *
     * @param  array  $providers
     * @return array
     */
    protected function compileManifest($providers)
    {
        // The service manifest should contain a list of all of the providers for
        // the application so we can compare it on each request to the service
        // and determine if the manifest should be recompiled or is current.
		// 服务清单应包含应用程序的所有提供者的列表，
		// 以便我们可以在每次请求服务时对其进行比较，
		// 并确定清单是否应该重新编译或是最新的。
        $manifest = $this->freshManifest($providers);

        foreach ($providers as $provider) {
            $instance = $this->createProvider($provider);

            // When recompiling the service manifest, we will spin through each of the
            // providers and check if it's a deferred provider or not. If so we'll
            // add it's provided services to the manifest and note the provider.
			// 在重新编译服务清单时，我们将逐一浏览提供商，并检查它是否是延迟加提供商。
			// 如果是这样，我们会将期提供的服务添加到清单中，并注明提供者。
            if ($instance->isDeferred()) {
                foreach ($instance->provides() as $service) {
                    $manifest['deferred'][$service] = $provider;
                }

                $manifest['when'][$provider] = $instance->when();
            }

            // If the service providers are not deferred, we will simply add it to an
            // array of eagerly loaded providers that will get registered on every
            // request to this application instead of "lazy" loading every time.
			// 如果服务商没有延期，我们只需将其添加到一系列加载量大的提供商。
            else {
                $manifest['eager'][] = $provider;
            }
        }

        return $this->writeManifest($manifest);
    }

    /**
     * Create a fresh service manifest data structure.
	 * 创建一个新的服务清单数据结构
     *
     * @param  array  $providers
     * @return array
     */
    protected function freshManifest(array $providers)
    {
        return ['providers' => $providers, 'eager' => [], 'deferred' => []];
    }

    /**
     * Write the service manifest file to disk.
	 * 写入服务清单至磁盘
     *
     * @param  array  $manifest
     * @return array
     *
     * @throws \Exception
     */
    public function writeManifest($manifest)
    {
        if (! is_writable($dirname = dirname($this->manifestPath))) {
            throw new Exception("The {$dirname} directory must be present and writable.");
        }

        $this->files->replace(
            $this->manifestPath, '<?php return '.var_export($manifest, true).';'
        );

        return array_merge(['when' => []], $manifest);
    }

    /**
     * Create a new provider instance.
	 * 创建新的提供者实例
     *
     * @param  string  $provider
     * @return \Illuminate\Support\ServiceProvider
     */
    public function createProvider($provider)
    {
        return new $provider($this->app);
    }
}

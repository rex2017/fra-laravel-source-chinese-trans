<?php
/**
 * 文件系统服务提供者
 */

namespace Illuminate\Filesystem;

use Illuminate\Support\ServiceProvider;

class FilesystemServiceProvider extends ServiceProvider
{
    /**
     * Register the service provider.
	 * 注册服务提供者
     *
     * @return void
     */
    public function register()
    {
        $this->registerNativeFilesystem();

        $this->registerFlysystem();
    }

    /**
     * Register the native filesystem implementation.
	 * 注册本机文件系统实现
     *
     * @return void
     */
    protected function registerNativeFilesystem()
    {
        $this->app->singleton('files', function () {
            return new Filesystem;
        });
    }

    /**
     * Register the driver based filesystem.
	 * 注册基于文件系统的驱动
     *
     * @return void
     */
    protected function registerFlysystem()
    {
        $this->registerManager();

        $this->app->singleton('filesystem.disk', function () {
            return $this->app['filesystem']->disk($this->getDefaultDriver());
        });

        $this->app->singleton('filesystem.cloud', function () {
            return $this->app['filesystem']->disk($this->getCloudDriver());
        });
    }

    /**
     * Register the filesystem manager.
	 * 注册文件系统管理者
     *
     * @return void
     */
    protected function registerManager()
    {
        $this->app->singleton('filesystem', function () {
            return new FilesystemManager($this->app);
        });
    }

    /**
     * Get the default file driver.
	 * 得到默认文件驱动
     *
     * @return string
     */
    protected function getDefaultDriver()
    {
        return $this->app['config']['filesystems.default'];
    }

    /**
     * Get the default cloud based file driver.
	 * 得到默认云驱动
     *
     * @return string
     */
    protected function getCloudDriver()
    {
        return $this->app['config']['filesystems.cloud'];
    }
}

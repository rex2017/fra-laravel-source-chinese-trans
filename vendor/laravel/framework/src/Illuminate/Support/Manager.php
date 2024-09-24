<?php
/**
 * 支持，管理抽象类
 */

namespace Illuminate\Support;

use Closure;
use Illuminate\Contracts\Container\Container;
use InvalidArgumentException;

abstract class Manager
{
    /**
     * The container instance.
	 * 容器实例
     *
     * @var \Illuminate\Contracts\Container\Container
     */
    protected $container;

    /**
     * The container instance.
	 * 容器实例
     *
     * @var \Illuminate\Contracts\Container\Container
     *
     * @deprecated Use the $container property instead.
     */
    protected $app;

    /**
     * The configuration repository instance.
	 * 配置存储库实例
     *
     * @var \Illuminate\Contracts\Config\Repository
     */
    protected $config;

    /**
     * The registered custom driver creators.
	 * 注册的自定义驱动程序创建者
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
    protected $drivers = [];

    /**
     * Create a new manager instance.
	 * 创建新的管理实例
     *
     * @param  \Illuminate\Contracts\Container\Container  $container
     * @return void
     */
    public function __construct(Container $container)
    {
        $this->app = $container;
        $this->container = $container;
        $this->config = $container->make('config');
    }

    /**
     * Get the default driver name.
	 * 得到默认驱动名称
     *
     * @return string
     */
    abstract public function getDefaultDriver();

    /**
     * Get a driver instance.
	 * 得到驱动实例
     *
     * @param  string  $driver
     * @return mixed
     *
     * @throws \InvalidArgumentException
     */
    public function driver($driver = null)
    {
        $driver = $driver ?: $this->getDefaultDriver();

        if (is_null($driver)) {
            throw new InvalidArgumentException(sprintf(
                'Unable to resolve NULL driver for [%s].', static::class
            ));
        }

        // If the given driver has not been created before, we will create the instances
        // here and cache it so we can return it next time very quickly. If there is
        // already a driver created by this name, we'll just return that instance.
		// 如果给定的驱动程序以前没有创建过，我们将在这里创建实例并缓存它，这样我们下次就可以很快地返回它。
		// 如果已经有一个以此名称创建的驱动程序，我们将只返回该实例。
        if (! isset($this->drivers[$driver])) {
            $this->drivers[$driver] = $this->createDriver($driver);
        }

        return $this->drivers[$driver];
    }

    /**
     * Create a new driver instance.
	 * 创建新的驱动实例
     *
     * @param  string  $driver
     * @return mixed
     *
     * @throws \InvalidArgumentException
     */
    protected function createDriver($driver)
    {
        // First, we will determine if a custom driver creator exists for the given driver and
        // if it does not we will check for a creator method for the driver. Custom creator
        // callbacks allow developers to build their own "drivers" easily using Closures.
		// 首先，我们将确定给定驱动程序是否存在自定义驱动程序创建者，
		// 如果不存在，我们将检查驱动程序的创建者方法。
		// 自定义创建者回调允许开发人员使用闭包轻松构建自己的"驱动程序"。
        if (isset($this->customCreators[$driver])) {
            return $this->callCustomCreator($driver);
        } else {
            $method = 'create'.Str::studly($driver).'Driver';

            if (method_exists($this, $method)) {
                return $this->$method();
            }
        }

        throw new InvalidArgumentException("Driver [$driver] not supported.");
    }

    /**
     * Call a custom driver creator.
	 * 调用自定义驱动创建者
     *
     * @param  string  $driver
     * @return mixed
     */
    protected function callCustomCreator($driver)
    {
        return $this->customCreators[$driver]($this->container);
    }

    /**
     * Register a custom driver creator Closure.
	 * 注册一个自定义驱动创建者闭包
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
     * Get all of the created "drivers".
	 * 得到所有创建者"驱动"
     *
     * @return array
     */
    public function getDrivers()
    {
        return $this->drivers;
    }

    /**
     * Dynamically call the default driver instance.
	 * 动态调取默认驱动实例
     *
     * @param  string  $method
     * @param  array  $parameters
     * @return mixed
     */
    public function __call($method, $parameters)
    {
        return $this->driver()->$method(...$parameters);
    }
}

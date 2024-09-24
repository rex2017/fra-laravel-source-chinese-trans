<?php
/**
 * 支持，压缩管理特征
 */

namespace Illuminate\Support\Traits;

use Illuminate\Contracts\Container\Container;
use Illuminate\Support\Fluent;

trait CapsuleManagerTrait
{
    /**
     * The current globally used instance.
	 * 当前全局使用的实例
     *
     * @var object
     */
    protected static $instance;

    /**
     * The container instance.
	 * 容器实例
     *
     * @var \Illuminate\Contracts\Container\Container
     */
    protected $container;

    /**
     * Setup the IoC container instance.
	 * 设置IoC容器实例
     *
     * @param  \Illuminate\Contracts\Container\Container  $container
     * @return void
     */
    protected function setupContainer(Container $container)
    {
        $this->container = $container;

        if (! $this->container->bound('config')) {
            $this->container->instance('config', new Fluent);
        }
    }

    /**
     * Make this capsule instance available globally.
	 * 使这个胶囊实例全局可用
     *
     * @return void
     */
    public function setAsGlobal()
    {
        static::$instance = $this;
    }

    /**
     * Get the IoC container instance.
	 * 获取IoC容器实例
     *
     * @return \Illuminate\Contracts\Container\Container
     */
    public function getContainer()
    {
        return $this->container;
    }

    /**
     * Set the IoC container instance.
	 * 设置IoC容器实例
     *
     * @param  \Illuminate\Contracts\Container\Container  $container
     * @return void
     */
    public function setContainer(Container $container)
    {
        $this->container = $container;
    }
}

<?php
/**
 * 基础，与容器交互
 */

namespace Illuminate\Foundation\Testing\Concerns;

use Closure;
use Illuminate\Foundation\Mix;
use Mockery;

trait InteractsWithContainer
{
    /**
     * The original Laravel Mix handler.
	 * 原始的Laravel Mix处理程序
     *
     * @var \Illuminate\Foundation\Mix|null
     */
    protected $originalMix;

    /**
     * Register an instance of an object in the container.
	 * 注册对象的实例在容器中
     *
     * @param  string  $abstract
     * @param  object  $instance
     * @return object
     */
    protected function swap($abstract, $instance)
    {
        return $this->instance($abstract, $instance);
    }

    /**
     * Register an instance of an object in the container.
	 * 注册对象的实例在容器中
     *
     * @param  string  $abstract
     * @param  object  $instance
     * @return object
     */
    protected function instance($abstract, $instance)
    {
        $this->app->instance($abstract, $instance);

        return $instance;
    }

    /**
     * Mock an instance of an object in the container.
	 * 模拟容器中对象的实例
     *
     * @param  string  $abstract
     * @param  \Closure|null  $mock
     * @return \Mockery\MockInterface
     */
    protected function mock($abstract, Closure $mock = null)
    {
        return $this->instance($abstract, Mockery::mock(...array_filter(func_get_args())));
    }

    /**
     * Mock a partial instance of an object in the container.
	 * 模拟容器中对象的部分实例
     *
     * @param  string  $abstract
     * @param  \Closure|null  $mock
     * @return \Mockery\MockInterface
     */
    protected function partialMock($abstract, Closure $mock = null)
    {
        return $this->instance($abstract, Mockery::mock(...array_filter(func_get_args()))->makePartial());
    }

    /**
     * Spy an instance of an object in the container.
	 * 监视容器中对象的实例
     *
     * @param  string  $abstract
     * @param  \Closure|null  $mock
     * @return \Mockery\MockInterface
     */
    protected function spy($abstract, Closure $mock = null)
    {
        return $this->instance($abstract, Mockery::spy(...array_filter(func_get_args())));
    }

    /**
     * Register an empty handler for Laravel Mix in the container.
	 * 注册一个空处理程序为Laravel Mix在容器中
     *
     * @return $this
     */
    protected function withoutMix()
    {
        if ($this->originalMix == null) {
            $this->originalMix = app(Mix::class);
        }

        $this->swap(Mix::class, function () {
            return '';
        });

        return $this;
    }

    /**
     * Register an empty handler for Laravel Mix in the container.
	 * 注册一个空处理程序为Laravel Mix在容器中
     *
     * @return $this
     */
    protected function withMix()
    {
        if ($this->originalMix) {
            $this->app->instance(Mix::class, $this->originalMix);
        }

        return $this;
    }
}

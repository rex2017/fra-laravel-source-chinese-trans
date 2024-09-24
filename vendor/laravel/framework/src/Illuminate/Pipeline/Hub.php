<?php
/**
 * 管道Hub路由
 */

namespace Illuminate\Pipeline;

use Closure;
use Illuminate\Contracts\Container\Container;
use Illuminate\Contracts\Pipeline\Hub as HubContract;

class Hub implements HubContract
{
    /**
     * The container implementation.
	 * 容器实现
     *
     * @var \Illuminate\Contracts\Container\Container|null
     */
    protected $container;

    /**
     * All of the available pipelines.
	 * 所有可用管道
     *
     * @var array
     */
    protected $pipelines = [];

    /**
     * Create a new Hub instance.
	 * 创建新的hub这实例
     *
     * @param  \Illuminate\Contracts\Container\Container|null  $container
     * @return void
     */
    public function __construct(Container $container = null)
    {
        $this->container = $container;
    }

    /**
     * Define the default named pipeline.
	 * 定义默认的命名管道
     *
     * @param  \Closure  $callback
     * @return void
     */
    public function defaults(Closure $callback)
    {
        return $this->pipeline('default', $callback);
    }

    /**
     * Define a new named pipeline.
	 * 定义新的命名管道
     *
     * @param  string  $name
     * @param  \Closure  $callback
     * @return void
     */
    public function pipeline($name, Closure $callback)
    {
        $this->pipelines[$name] = $callback;
    }

    /**
     * Send an object through one of the available pipelines.
	 * 发送对象通过一个可用的管道
     *
     * @param  mixed  $object
     * @param  string|null  $pipeline
     * @return mixed
     */
    public function pipe($object, $pipeline = null)
    {
        $pipeline = $pipeline ?: 'default';

        return call_user_func(
            $this->pipelines[$pipeline], new Pipeline($this->container), $object
        );
    }
}

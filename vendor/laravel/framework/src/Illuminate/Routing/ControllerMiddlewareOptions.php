<?php
/**
 * 路由控制器中间件选项
 */

namespace Illuminate\Routing;

class ControllerMiddlewareOptions
{
    /**
     * The middleware options.
	 * 中间件选项
     *
     * @var array
     */
    protected $options;

    /**
     * Create a new middleware option instance.
	 * 创建新的中间件选项实例
     *
     * @param  array  $options
     * @return void
     */
    public function __construct(array &$options)
    {
        $this->options = &$options;
    }

    /**
     * Set the controller methods the middleware should apply to.
	 * 设置中间件应该应用的控制器方法
     *
     * @param  array|string|dynamic  $methods
     * @return $this
     */
    public function only($methods)
    {
        $this->options['only'] = is_array($methods) ? $methods : func_get_args();

        return $this;
    }

    /**
     * Set the controller methods the middleware should exclude.
	 * 设置中间件应该排除的控制器方法
     *
     * @param  array|string|dynamic  $methods
     * @return $this
     */
    public function except($methods)
    {
        $this->options['except'] = is_array($methods) ? $methods : func_get_args();

        return $this;
    }
}

<?php
/**
 * 契约，视图工厂接口
 */

namespace Illuminate\Contracts\View;

interface Factory
{
    /**
     * Determine if a given view exists.
	 * 确定视图是否存在
     *
     * @param  string  $view
     * @return bool
     */
    public function exists($view);

    /**
     * Get the evaluated view contents for the given path.
	 * 得到给定路径的求值视图内容
     *
     * @param  string  $path
     * @param  \Illuminate\Contracts\Support\Arrayable|array  $data
     * @param  array  $mergeData
     * @return \Illuminate\Contracts\View\View
     */
    public function file($path, $data = [], $mergeData = []);

    /**
     * Get the evaluated view contents for the given view.
	 * 得到给定视图的求值视图内容
     *
     * @param  string  $view
     * @param  \Illuminate\Contracts\Support\Arrayable|array  $data
     * @param  array  $mergeData
     * @return \Illuminate\Contracts\View\View
     */
    public function make($view, $data = [], $mergeData = []);

    /**
     * Add a piece of shared data to the environment.
	 * 添加一段共享数据至环境
     *
     * @param  array|string  $key
     * @param  mixed  $value
     * @return mixed
     */
    public function share($key, $value = null);

    /**
     * Register a view composer event.
	 * 注册一个新的视图事件
     *
     * @param  array|string  $views
     * @param  \Closure|string  $callback
     * @return array
     */
    public function composer($views, $callback);

    /**
     * Register a view creator event.
	 * 注册一个视图创建事件
     *
     * @param  array|string  $views
     * @param  \Closure|string  $callback
     * @return array
     */
    public function creator($views, $callback);

    /**
     * Add a new namespace to the loader.
	 * 添加一个新的命名空间
     *
     * @param  string  $namespace
     * @param  string|array  $hints
     * @return $this
     */
    public function addNamespace($namespace, $hints);

    /**
     * Replace the namespace hints for the given namespace.
	 * 替换给定名称空间的名称空间提示
     *
     * @param  string  $namespace
     * @param  string|array  $hints
     * @return $this
     */
    public function replaceNamespace($namespace, $hints);
}

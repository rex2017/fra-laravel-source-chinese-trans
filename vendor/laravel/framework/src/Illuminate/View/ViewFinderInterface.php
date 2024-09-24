<?php
/**
 * 视图寻找接口
 */

namespace Illuminate\View;

interface ViewFinderInterface
{
    /**
     * Hint path delimiter value.
	 * 提示路径分配符值
     *
     * @var string
     */
    const HINT_PATH_DELIMITER = '::';

    /**
     * Get the fully qualified location of the view.
	 * 得到视图的完全限定位置
     *
     * @param  string  $view
     * @return string
     */
    public function find($view);

    /**
     * Add a location to the finder.
	 * 向查找器添加位置
     *
     * @param  string  $location
     * @return void
     */
    public function addLocation($location);

    /**
     * Add a namespace hint to the finder.
	 * 向查找器添加名称空间提示
     *
     * @param  string  $namespace
     * @param  string|array  $hints
     * @return void
     */
    public function addNamespace($namespace, $hints);

    /**
     * Prepend a namespace hint to the finder.
	 * 添加一个名称空间提示至查找器
     *
     * @param  string  $namespace
     * @param  string|array  $hints
     * @return void
     */
    public function prependNamespace($namespace, $hints);

    /**
     * Replace the namespace hints for the given namespace.
	 * 替换给定名称空间的名称空间提示
     *
     * @param  string  $namespace
     * @param  string|array  $hints
     * @return void
     */
    public function replaceNamespace($namespace, $hints);

    /**
     * Add a valid view extension to the finder.
	 * 添加有效的视图扩展至查找器
     *
     * @param  string  $extension
     * @return void
     */
    public function addExtension($extension);

    /**
     * Flush the cache of located views.
	 * 刷新本地缓存视图
     *
     * @return void
     */
    public function flush();
}

<?php
/**
 * 文件视图查看器
 */

namespace Illuminate\View;

use Illuminate\Filesystem\Filesystem;
use InvalidArgumentException;

class FileViewFinder implements ViewFinderInterface
{
    /**
     * The filesystem instance.
	 * 文件系统实例
     *
     * @var \Illuminate\Filesystem\Filesystem
     */
    protected $files;

    /**
     * The array of active view paths.
	 * 视图路径
     *
     * @var array
     */
    protected $paths;

    /**
     * The array of views that have been located.
	 * 已定位的视图数组
     *
     * @var array
     */
    protected $views = [];

    /**
     * The namespace to file path hints.
	 * 文件路径提示
     *
     * @var array
     */
    protected $hints = [];

    /**
     * Register a view extension with the finder.
	 * 注册视图后缀
     *
     * @var array
     */
    protected $extensions = ['blade.php', 'php', 'css', 'html'];

    /**
     * Create a new file view loader instance.
	 * 创建新的文件视图加载实例
     *
     * @param  \Illuminate\Filesystem\Filesystem  $files
     * @param  array  $paths
     * @param  array|null  $extensions
     * @return void
     */
    public function __construct(Filesystem $files, array $paths, array $extensions = null)
    {
        $this->files = $files;
        $this->paths = array_map([$this, 'resolvePath'], $paths);

        if (isset($extensions)) {
            $this->extensions = $extensions;
        }
    }

    /**
     * Get the fully qualified location of the view.
	 * 得到视图的完全限定位置
     *
     * @param  string  $name
     * @return string
     */
    public function find($name)
    {
        if (isset($this->views[$name])) {
            return $this->views[$name];
        }

        if ($this->hasHintInformation($name = trim($name))) {
            return $this->views[$name] = $this->findNamespacedView($name);
        }

        return $this->views[$name] = $this->findInPaths($name, $this->paths);
    }

    /**
     * Get the path to a template with a named path.
	 * 得到具有命名路径的模板的路径
     *
     * @param  string  $name
     * @return string
     */
    protected function findNamespacedView($name)
    {
        [$namespace, $view] = $this->parseNamespaceSegments($name);

        return $this->findInPaths($view, $this->hints[$namespace]);
    }

    /**
     * Get the segments of a template with a named path.
	 * 得到带有命名路径的模板片段
     *
     * @param  string  $name
     * @return array
     *
     * @throws \InvalidArgumentException
     */
    protected function parseNamespaceSegments($name)
    {
        $segments = explode(static::HINT_PATH_DELIMITER, $name);

        if (count($segments) !== 2) {
            throw new InvalidArgumentException("View [{$name}] has an invalid name.");
        }

        if (! isset($this->hints[$segments[0]])) {
            throw new InvalidArgumentException("No hint path defined for [{$segments[0]}].");
        }

        return $segments;
    }

    /**
     * Find the given view in the list of paths.
	 * 查找给定视图在路径列表中
     *
     * @param  string  $name
     * @param  array  $paths
     * @return string
     *
     * @throws \InvalidArgumentException
     */
    protected function findInPaths($name, $paths)
    {
        foreach ((array) $paths as $path) {
            foreach ($this->getPossibleViewFiles($name) as $file) {
                if ($this->files->exists($viewPath = $path.'/'.$file)) {
                    return $viewPath;
                }
            }
        }

        throw new InvalidArgumentException("View [{$name}] not found.");
    }

    /**
     * Get an array of possible view files.
	 * 得到可能的视图文件的数组
     *
     * @param  string  $name
     * @return array
     */
    protected function getPossibleViewFiles($name)
    {
        return array_map(function ($extension) use ($name) {
            return str_replace('.', '/', $name).'.'.$extension;
        }, $this->extensions);
    }

    /**
     * Add a location to the finder.
	 * 添加位置至查找器
     *
     * @param  string  $location
     * @return void
     */
    public function addLocation($location)
    {
        $this->paths[] = $this->resolvePath($location);
    }

    /**
     * Prepend a location to the finder.
	 * 将位置添加到查找器
     *
     * @param  string  $location
     * @return void
     */
    public function prependLocation($location)
    {
        array_unshift($this->paths, $this->resolvePath($location));
    }

    /**
     * Resolve the path.
	 * 解析路径
     *
     * @param  string  $path
     * @return string
     */
    protected function resolvePath($path)
    {
        return realpath($path) ?: $path;
    }

    /**
     * Add a namespace hint to the finder.
	 * 添加命名空间
     *
     * @param  string  $namespace
     * @param  string|array  $hints
     * @return void
     */
    public function addNamespace($namespace, $hints)
    {
        $hints = (array) $hints;

        if (isset($this->hints[$namespace])) {
            $hints = array_merge($this->hints[$namespace], $hints);
        }

        $this->hints[$namespace] = $hints;
    }

    /**
     * Prepend a namespace hint to the finder.
	 * 向查找器添加一个名称空间提示
     *
     * @param  string  $namespace
     * @param  string|array  $hints
     * @return void
     */
    public function prependNamespace($namespace, $hints)
    {
        $hints = (array) $hints;

        if (isset($this->hints[$namespace])) {
            $hints = array_merge($hints, $this->hints[$namespace]);
        }

        $this->hints[$namespace] = $hints;
    }

    /**
     * Replace the namespace hints for the given namespace.
	 * 替换给定名称空间的名称空间提示
     *
     * @param  string  $namespace
     * @param  string|array  $hints
     * @return void
     */
    public function replaceNamespace($namespace, $hints)
    {
        $this->hints[$namespace] = (array) $hints;
    }

    /**
     * Register an extension with the view finder.
	 * 注册扩展
     *
     * @param  string  $extension
     * @return void
     */
    public function addExtension($extension)
    {
        if (($index = array_search($extension, $this->extensions)) !== false) {
            unset($this->extensions[$index]);
        }

        array_unshift($this->extensions, $extension);
    }

    /**
     * Returns whether or not the view name has any hint information.
	 * 返回视图名是否有任何提示信息
     *
     * @param  string  $name
     * @return bool
     */
    public function hasHintInformation($name)
    {
        return strpos($name, static::HINT_PATH_DELIMITER) > 0;
    }

    /**
     * Flush the cache of located views.
	 * 清空本地视图
     *
     * @return void
     */
    public function flush()
    {
        $this->views = [];
    }

    /**
     * Get the filesystem instance.
	 * 得到文件系统实例
     *
     * @return \Illuminate\Filesystem\Filesystem
     */
    public function getFilesystem()
    {
        return $this->files;
    }

    /**
     * Set the active view paths.
	 * 设置主动视图路径
     *
     * @param  array  $paths
     * @return $this
     */
    public function setPaths($paths)
    {
        $this->paths = $paths;

        return $this;
    }

    /**
     * Get the active view paths.
	 * 得到视图路径
     *
     * @return array
     */
    public function getPaths()
    {
        return $this->paths;
    }

    /**
     * Get the views that have been located.
	 * 得到已定位的视图
     *
     * @return array
     */
    public function getViews()
    {
        return $this->views;
    }

    /**
     * Get the namespace to file path hints.
	 * 得到文件路径提示
     *
     * @return array
     */
    public function getHints()
    {
        return $this->hints;
    }

    /**
     * Get registered extensions.
	 * 得到注册扩展名
     *
     * @return array
     */
    public function getExtensions()
    {
        return $this->extensions;
    }
}

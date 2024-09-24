<?php
/**
 * 翻译文件加载
 */

namespace Illuminate\Translation;

use Illuminate\Contracts\Translation\Loader;
use Illuminate\Filesystem\Filesystem;
use RuntimeException;

class FileLoader implements Loader
{
    /**
     * The filesystem instance.
	 * 文件系统实例
     *
     * @var \Illuminate\Filesystem\Filesystem
     */
    protected $files;

    /**
     * The default path for the loader.
	 * 默认路径
     *
     * @var string
     */
    protected $path;

    /**
     * All of the registered paths to JSON translation files.
	 * 所有已注册命名空间的数组
     *
     * @var array
     */
    protected $jsonPaths = [];

    /**
     * All of the namespace hints.
	 * 所有名称空间提示
     *
     * @var array
     */
    protected $hints = [];

    /**
     * Create a new file loader instance.
	 * 创建新的文件加载实例
     *
     * @param  \Illuminate\Filesystem\Filesystem  $files
     * @param  string  $path
     * @return void
     */
    public function __construct(Filesystem $files, $path)
    {
        $this->path = $path;
        $this->files = $files;
    }

    /**
     * Load the messages for the given locale.
	 * 加载给定区域设置的消息
     *
     * @param  string  $locale
     * @param  string  $group
     * @param  string|null  $namespace
     * @return array
     */
    public function load($locale, $group, $namespace = null)
    {
        if ($group === '*' && $namespace === '*') {
            return $this->loadJsonPaths($locale);
        }

        if (is_null($namespace) || $namespace === '*') {
            return $this->loadPath($this->path, $locale, $group);
        }

        return $this->loadNamespaced($locale, $group, $namespace);
    }

    /**
     * Load a namespaced translation group.
	 * 加载一个名称空间翻译组
     *
     * @param  string  $locale
     * @param  string  $group
     * @param  string  $namespace
     * @return array
     */
    protected function loadNamespaced($locale, $group, $namespace)
    {
        if (isset($this->hints[$namespace])) {
            $lines = $this->loadPath($this->hints[$namespace], $locale, $group);

            return $this->loadNamespaceOverrides($lines, $locale, $group, $namespace);
        }

        return [];
    }

    /**
     * Load a local namespaced translation group for overrides.
	 * 为覆盖加载本地命名空间翻译组
     *
     * @param  array  $lines
     * @param  string  $locale
     * @param  string  $group
     * @param  string  $namespace
     * @return array
     */
    protected function loadNamespaceOverrides(array $lines, $locale, $group, $namespace)
    {
        $file = "{$this->path}/vendor/{$namespace}/{$locale}/{$group}.php";

        if ($this->files->exists($file)) {
            return array_replace_recursive($lines, $this->files->getRequire($file));
        }

        return $lines;
    }

    /**
     * Load a locale from a given path.
	 * 加载区域设置从给定路径
     *
     * @param  string  $path
     * @param  string  $locale
     * @param  string  $group
     * @return array
     */
    protected function loadPath($path, $locale, $group)
    {
        if ($this->files->exists($full = "{$path}/{$locale}/{$group}.php")) {
            return $this->files->getRequire($full);
        }

        return [];
    }

    /**
     * Load a locale from the given JSON file path.
	 * 从给定的JSON文件路径加载区域设置
     *
     * @param  string  $locale
     * @return array
     *
     * @throws \RuntimeException
     */
    protected function loadJsonPaths($locale)
    {
        return collect(array_merge($this->jsonPaths, [$this->path]))
            ->reduce(function ($output, $path) use ($locale) {
                if ($this->files->exists($full = "{$path}/{$locale}.json")) {
                    $decoded = json_decode($this->files->get($full), true);

                    if (is_null($decoded) || json_last_error() !== JSON_ERROR_NONE) {
                        throw new RuntimeException("Translation file [{$full}] contains an invalid JSON structure.");
                    }

                    $output = array_merge($output, $decoded);
                }

                return $output;
            }, []);
    }

    /**
     * Add a new namespace to the loader.
	 * 添加一个新的命名空间至加载器
     *
     * @param  string  $namespace
     * @param  string  $hint
     * @return void
     */
    public function addNamespace($namespace, $hint)
    {
        $this->hints[$namespace] = $hint;
    }

    /**
     * Add a new JSON path to the loader.
	 * 向加载器添加一个新的JSON路径
     *
     * @param  string  $path
     * @return void
     */
    public function addJsonPath($path)
    {
        $this->jsonPaths[] = $path;
    }

    /**
     * Get an array of all the registered namespaces.
	 * 得到所有已注册名称空间的数组
     *
     * @return array
     */
    public function namespaces()
    {
        return $this->hints;
    }
}

<?php
/**
 * 契约，翻译加载接口
 */

namespace Illuminate\Contracts\Translation;

interface Loader
{
    /**
     * Load the messages for the given locale.
	 * 加载本地语言包
     *
     * @param  string  $locale
     * @param  string  $group
     * @param  string|null  $namespace
     * @return array
     */
    public function load($locale, $group, $namespace = null);

    /**
     * Add a new namespace to the loader.
	 * 添加新的命名空间
     *
     * @param  string  $namespace
     * @param  string  $hint
     * @return void
     */
    public function addNamespace($namespace, $hint);

    /**
     * Add a new JSON path to the loader.
	 * 添加新的json路径
     *
     * @param  string  $path
     * @return void
     */
    public function addJsonPath($path);

    /**
     * Get an array of all the registered namespaces.
	 * 得到命名空间
     *
     * @return array
     */
    public function namespaces();
}

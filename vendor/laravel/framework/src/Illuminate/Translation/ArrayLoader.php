<?php
/**
 * 翻译数组加载
 */

namespace Illuminate\Translation;

use Illuminate\Contracts\Translation\Loader;

class ArrayLoader implements Loader
{
    /**
     * All of the translation messages.
	 * 所有翻译信息
     *
     * @var array
     */
    protected $messages = [];

    /**
     * Load the messages for the given locale.
	 * 导入本地信息
     *
     * @param  string  $locale
     * @param  string  $group
     * @param  string|null  $namespace
     * @return array
     */
    public function load($locale, $group, $namespace = null)
    {
        $namespace = $namespace ?: '*';

        return $this->messages[$namespace][$locale][$group] ?? [];
    }

    /**
     * Add a new namespace to the loader.
	 * 添加新的命名空间
     *
     * @param  string  $namespace
     * @param  string  $hint
     * @return void
     */
    public function addNamespace($namespace, $hint)
    {
        //
    }

    /**
     * Add a new JSON path to the loader.
	 * 添加一个新的JSON路径向加载器
     *
     * @param  string  $path
     * @return void
     */
    public function addJsonPath($path)
    {
        //
    }

    /**
     * Add messages to the loader.
	 * 添加消息至加载程序
     *
     * @param  string  $locale
     * @param  string  $group
     * @param  array  $messages
     * @param  string|null  $namespace
     * @return $this
     */
    public function addMessages($locale, $group, array $messages, $namespace = null)
    {
        $namespace = $namespace ?: '*';

        $this->messages[$namespace][$locale][$group] = $messages;

        return $this;
    }

    /**
     * Get an array of all the registered namespaces.
	 * 得到所有已注册命名空间的数组
     *
     * @return array
     */
    public function namespaces()
    {
        return [];
    }
}

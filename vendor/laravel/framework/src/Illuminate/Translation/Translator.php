<?php
/**
 * 翻译
 */

namespace Illuminate\Translation;

use Countable;
use Illuminate\Contracts\Translation\Loader;
use Illuminate\Contracts\Translation\Translator as TranslatorContract;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\NamespacedItemResolver;
use Illuminate\Support\Str;
use Illuminate\Support\Traits\Macroable;
use InvalidArgumentException;

class Translator extends NamespacedItemResolver implements TranslatorContract
{
    use Macroable;

    /**
     * The loader implementation.
	 * 加载实现
     *
     * @var \Illuminate\Contracts\Translation\Loader
     */
    protected $loader;

    /**
     * The default locale being used by the translator.
	 * 注册翻译行加载程序
     *
     * @var string
     */
    protected $locale;

    /**
     * The fallback locale used by the translator.
	 * 翻译程序使用的回退语言环境
     *
     * @var string
     */
    protected $fallback;

    /**
     * The array of loaded translation groups.
	 * 加载的翻译组数组
     *
     * @var array
     */
    protected $loaded = [];

    /**
     * The message selector.
	 * 消息选择器
     *
     * @var \Illuminate\Translation\MessageSelector
     */
    protected $selector;

    /**
     * Create a new translator instance.
	 * 创建新的翻译器实例
     *
     * @param  \Illuminate\Contracts\Translation\Loader  $loader
     * @param  string  $locale
     * @return void
     */
    public function __construct(Loader $loader, $locale)
    {
        $this->loader = $loader;

        $this->setLocale($locale);
    }

    /**
     * Determine if a translation exists for a given locale.
	 * 确定给定语言环境是否存在翻译
     *
     * @param  string  $key
     * @param  string|null  $locale
     * @return bool
     */
    public function hasForLocale($key, $locale = null)
    {
        return $this->has($key, $locale, false);
    }

    /**
     * Determine if a translation exists.
	 * 确定是否存在翻译
     *
     * @param  string  $key
     * @param  string|null  $locale
     * @param  bool  $fallback
     * @return bool
     */
    public function has($key, $locale = null, $fallback = true)
    {
        return $this->get($key, [], $locale, $fallback) !== $key;
    }

    /**
     * Get the translation for the given key.
	 * 得到给定键的翻译
     *
     * @param  string  $key
     * @param  array  $replace
     * @param  string|null  $locale
     * @param  bool  $fallback
     * @return string|array
     */
    public function get($key, array $replace = [], $locale = null, $fallback = true)
    {
        $locale = $locale ?: $this->locale;

        // For JSON translations, there is only one file per locale, so we will simply load
        // that file and then we will be ready to check the array for the key. These are
        // only one level deep so we do not need to do any fancy searching through it.
		// 对于JSON翻译，每个语言环境只有一个文件，因此我们只需加载该文件，然后就可以检查数组中的键了。
		// 这些只是一个层次的深度，所以我们不需要做任何花哨的搜索。
        $this->load('*', '*', $locale);

        $line = $this->loaded['*']['*'][$locale][$key] ?? null;

        // If we can't find a translation for the JSON key, we will attempt to translate it
        // using the typical translation file. This way developers can always just use a
        // helper such as __ instead of having to pick between trans or __ with views.
		// 如果我们找不到JSON密钥的翻译，我们将尝试使用典型的翻译文件进行翻译。
		// 这样，开发人员就可以始终使用__等辅助工具，而不必在trans或__与视图之间进行选择。
        if (! isset($line)) {
            [$namespace, $group, $item] = $this->parseKey($key);

            // Here we will get the locale that should be used for the language line. If one
            // was not passed, we will use the default locales which was given to us when
            // the translator was instantiated. Then, we can load the lines and return.
			// 在这里，我们将获得应该用于语言行的区域设置。
			// 如果没有通过，我们将使用翻译器实例化时提供的默认语言环境。然后，我们可以加载行并返回。
            $locales = $fallback ? $this->localeArray($locale) : [$locale];

            foreach ($locales as $locale) {
                if (! is_null($line = $this->getLine(
                    $namespace, $group, $locale, $item, $replace
                ))) {
                    return $line ?? $key;
                }
            }
        }

        // If the line doesn't exist, we will return back the key which was requested as
        // that will be quick to spot in the UI if language keys are wrong or missing
        // from the application's language files. Otherwise we can return the line.
		// 如果该行不存在，我们将返回所请求的键，因为如果应用程序的语言文件中的语言键错误或缺失，
		// UI中会很快发现。否则，我们可以返回线路。
        return $this->makeReplacements($line ?: $key, $replace);
    }

    /**
     * Get a translation according to an integer value.
	 * 根据整数值获取翻译
     *
     * @param  string  $key
     * @param  \Countable|int|array  $number
     * @param  array  $replace
     * @param  string|null  $locale
     * @return string
     */
    public function choice($key, $number, array $replace = [], $locale = null)
    {
        $line = $this->get(
            $key, $replace, $locale = $this->localeForChoice($locale)
        );

        // If the given "number" is actually an array or countable we will simply count the
        // number of elements in an instance. This allows developers to pass an array of
        // items without having to count it on their end first which gives bad syntax.
		// 如果给定的"数字"实际上是一个数组或可数数组，我们只需计算实例中的元素数量。
		// 这允许开发人员传递一个项目数组，而不必先在末尾计数，这会导致语法错误。
        if (is_array($number) || $number instanceof Countable) {
            $number = count($number);
        }

        $replace['count'] = $number;

        return $this->makeReplacements(
            $this->getSelector()->choose($line, $number, $locale), $replace
        );
    }

    /**
     * Get the proper locale for a choice operation.
	 * 得到适当的区域设置为选择操作
     *
     * @param  string|null  $locale
     * @return string
     */
    protected function localeForChoice($locale)
    {
        return $locale ?: $this->locale ?: $this->fallback;
    }

    /**
     * Retrieve a language line out the loaded array.
	 * 检索语言行从加载的数组中
     *
     * @param  string  $namespace
     * @param  string  $group
     * @param  string  $locale
     * @param  string  $item
     * @param  array  $replace
     * @return string|array|null
     */
    protected function getLine($namespace, $group, $locale, $item, array $replace)
    {
        $this->load($namespace, $group, $locale);

        $line = Arr::get($this->loaded[$namespace][$group][$locale], $item);

        if (is_string($line)) {
            return $this->makeReplacements($line, $replace);
        } elseif (is_array($line) && count($line) > 0) {
            foreach ($line as $key => $value) {
                $line[$key] = $this->makeReplacements($value, $replace);
            }

            return $line;
        }
    }

    /**
     * Make the place-holder replacements on a line.
	 * 替换占位符在一行上
     *
     * @param  string  $line
     * @param  array  $replace
     * @return string
     */
    protected function makeReplacements($line, array $replace)
    {
        if (empty($replace)) {
            return $line;
        }

        $replace = $this->sortReplacements($replace);

        foreach ($replace as $key => $value) {
            $line = str_replace(
                [':'.$key, ':'.Str::upper($key), ':'.Str::ucfirst($key)],
                [$value, Str::upper($value), Str::ucfirst($value)],
                $line
            );
        }

        return $line;
    }

    /**
     * Sort the replacements array.
	 * 对替换数组进行排序
     *
     * @param  array  $replace
     * @return array
     */
    protected function sortReplacements(array $replace)
    {
        return (new Collection($replace))->sortBy(function ($value, $key) {
            return mb_strlen($key) * -1;
        })->all();
    }

    /**
     * Add translation lines to the given locale.
	 * 添加翻译行向给定的语言环境
     *
     * @param  array  $lines
     * @param  string  $locale
     * @param  string  $namespace
     * @return void
     */
    public function addLines(array $lines, $locale, $namespace = '*')
    {
        foreach ($lines as $key => $value) {
            [$group, $item] = explode('.', $key, 2);

            Arr::set($this->loaded, "$namespace.$group.$locale.$item", $value);
        }
    }

    /**
     * Load the specified language group.
	 * 加载指定的语言组
     *
     * @param  string  $namespace
     * @param  string  $group
     * @param  string  $locale
     * @return void
     */
    public function load($namespace, $group, $locale)
    {
        if ($this->isLoaded($namespace, $group, $locale)) {
            return;
        }

        // The loader is responsible for returning the array of language lines for the
        // given namespace, group, and locale. We'll set the lines in this array of
        // lines that have already been loaded so that we can easily access them.
		// 加载器负责返回给定名称空间、组和区域设置的语言行数组。
		// 我们将在这个已经加载的行数组中设置行，以便我们可以轻松访问它们。
        $lines = $this->loader->load($locale, $group, $namespace);

        $this->loaded[$namespace][$group][$locale] = $lines;
    }

    /**
     * Determine if the given group has been loaded.
	 * 确定是否已加载给定的组
     *
     * @param  string  $namespace
     * @param  string  $group
     * @param  string  $locale
     * @return bool
     */
    protected function isLoaded($namespace, $group, $locale)
    {
        return isset($this->loaded[$namespace][$group][$locale]);
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
        $this->loader->addNamespace($namespace, $hint);
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
        $this->loader->addJsonPath($path);
    }

    /**
     * Parse a key into namespace, group, and item.
	 * 将键解析为名称空间、组和项
     *
     * @param  string  $key
     * @return array
     */
    public function parseKey($key)
    {
        $segments = parent::parseKey($key);

        if (is_null($segments[0])) {
            $segments[0] = '*';
        }

        return $segments;
    }

    /**
     * Get the array of locales to be checked.
	 * 得到要检查的区域设置数组
     *
     * @param  string|null  $locale
     * @return array
     */
    protected function localeArray($locale)
    {
        return array_filter([$locale ?: $this->locale, $this->fallback]);
    }

    /**
     * Get the message selector instance.
	 * 得到消息选择器实例
     *
     * @return \Illuminate\Translation\MessageSelector
     */
    public function getSelector()
    {
        if (! isset($this->selector)) {
            $this->selector = new MessageSelector;
        }

        return $this->selector;
    }

    /**
     * Set the message selector instance.
	 * 设置消息选择器实例
     *
     * @param  \Illuminate\Translation\MessageSelector  $selector
     * @return void
     */
    public function setSelector(MessageSelector $selector)
    {
        $this->selector = $selector;
    }

    /**
     * Get the language line loader implementation.
	 * 得到语言行加载器实现
     *
     * @return \Illuminate\Contracts\Translation\Loader
     */
    public function getLoader()
    {
        return $this->loader;
    }

    /**
     * Get the default locale being used.
	 * 得到正在使用的默认区域设置
     *
     * @return string
     */
    public function locale()
    {
        return $this->getLocale();
    }

    /**
     * Get the default locale being used.
	 * 得到正在使用的默认区域设置
     *
     * @return string
     */
    public function getLocale()
    {
        return $this->locale;
    }

    /**
     * Set the default locale.
	 * 设置默认语言环境
     *
     * @param  string  $locale
     * @return void
     */
    public function setLocale($locale)
    {
        if (Str::contains($locale, ['/', '\\'])) {
            throw new InvalidArgumentException('Invalid characters present in locale.');
        }

        $this->locale = $locale;
    }

    /**
     * Get the fallback locale being used.
	 * 得到正在使用的回退区域设置
     *
     * @return string
     */
    public function getFallback()
    {
        return $this->fallback;
    }

    /**
     * Set the fallback locale being used.
	 * 设置正在使用的回退区域设置
     *
     * @param  string  $fallback
     * @return void
     */
    public function setFallback($fallback)
    {
        $this->fallback = $fallback;
    }

    /**
     * Set the loaded translation groups.
	 * 设置加载的翻译组
     *
     * @param  array  $loaded
     * @return void
     */
    public function setLoaded(array $loaded)
    {
        $this->loaded = $loaded;
    }
}

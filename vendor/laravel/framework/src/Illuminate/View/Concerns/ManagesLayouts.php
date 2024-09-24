<?php
/**
 * 视图，管理布局
 */

namespace Illuminate\View\Concerns;

use Illuminate\Contracts\View\View;
use Illuminate\Support\Str;
use InvalidArgumentException;

trait ManagesLayouts
{
    /**
     * All of the finished, captured sections.
	 * 所有完成的、捕获的部分。
     *
     * @var array
     */
    protected $sections = [];

    /**
     * The stack of in-progress sections.
	 * 正在进行的部分的堆栈
     *
     * @var array
     */
    protected $sectionStack = [];

    /**
     * The parent placeholder for the request.
	 * 请求的父占位符
     *
     * @var mixed
     */
    protected static $parentPlaceholder = [];

    /**
     * The parent placeholder salt for the request.
	 * 请求的父占位符
     *
     * @var string
     */
    protected static $parentPlaceholderSalt;

    /**
     * Start injecting content into a section.
	 * 开始向部分注入内容
     *
     * @param  string  $section
     * @param  string|null  $content
     * @return void
     */
    public function startSection($section, $content = null)
    {
        if ($content === null) {
            if (ob_start()) {
                $this->sectionStack[] = $section;
            }
        } else {
            $this->extendSection($section, $content instanceof View ? $content : e($content));
        }
    }

    /**
     * Inject inline content into a section.
	 * 将内联内容注入节中
     *
     * @param  string  $section
     * @param  string  $content
     * @return void
     */
    public function inject($section, $content)
    {
        $this->startSection($section, $content);
    }

    /**
     * Stop injecting content into a section and return its contents.
	 * 停止向节中注入内容并返回其内容
     *
     * @return string
     */
    public function yieldSection()
    {
        if (empty($this->sectionStack)) {
            return '';
        }

        return $this->yieldContent($this->stopSection());
    }

    /**
     * Stop injecting content into a section.
	 * 停止向节中注入内容
     *
     * @param  bool  $overwrite
     * @return string
     *
     * @throws \InvalidArgumentException
     */
    public function stopSection($overwrite = false)
    {
        if (empty($this->sectionStack)) {
            throw new InvalidArgumentException('Cannot end a section without first starting one.');
        }

        $last = array_pop($this->sectionStack);

        if ($overwrite) {
            $this->sections[$last] = ob_get_clean();
        } else {
            $this->extendSection($last, ob_get_clean());
        }

        return $last;
    }

    /**
     * Stop injecting content into a section and append it.
	 * 停止向节中注入内容并将其追加
     *
     * @return string
     *
     * @throws \InvalidArgumentException
     */
    public function appendSection()
    {
        if (empty($this->sectionStack)) {
            throw new InvalidArgumentException('Cannot end a section without first starting one.');
        }

        $last = array_pop($this->sectionStack);

        if (isset($this->sections[$last])) {
            $this->sections[$last] .= ob_get_clean();
        } else {
            $this->sections[$last] = ob_get_clean();
        }

        return $last;
    }

    /**
     * Append content to a given section.
	 * 追加内容向给定的部分
     *
     * @param  string  $section
     * @param  string  $content
     * @return void
     */
    protected function extendSection($section, $content)
    {
        if (isset($this->sections[$section])) {
            $content = str_replace(static::parentPlaceholder($section), $content, $this->sections[$section]);
        }

        $this->sections[$section] = $content;
    }

    /**
     * Get the string contents of a section.
	 * 得到节的字符串内容
     *
     * @param  string  $section
     * @param  string  $default
     * @return string
     */
    public function yieldContent($section, $default = '')
    {
        $sectionContent = $default instanceof View ? $default : e($default);

        if (isset($this->sections[$section])) {
            $sectionContent = $this->sections[$section];
        }

        $sectionContent = str_replace('@@parent', '--parent--holder--', $sectionContent);

        return str_replace(
            '--parent--holder--', '@parent', str_replace(static::parentPlaceholder($section), '', $sectionContent)
        );
    }

    /**
     * Get the parent placeholder for the current request.
	 * 得到当前请求的父占位符
     *
     * @param  string  $section
     * @return string
     */
    public static function parentPlaceholder($section = '')
    {
        if (! isset(static::$parentPlaceholder[$section])) {
            $salt = static::parentPlaceholderSalt();

            static::$parentPlaceholder[$section] = '##parent-placeholder-'.sha1($salt.$section).'##';
        }

        return static::$parentPlaceholder[$section];
    }

    /**
     * Get the parent placeholder salt.
	 * 得到父占位符salt
     *
     * @return string
     */
    protected static function parentPlaceholderSalt()
    {
        if (! static::$parentPlaceholderSalt) {
            return static::$parentPlaceholderSalt = Str::random(40);
        }

        return static::$parentPlaceholderSalt;
    }

    /**
     * Check if section exists.
	 * 检查section是否存在
     *
     * @param  string  $name
     * @return bool
     */
    public function hasSection($name)
    {
        return array_key_exists($name, $this->sections);
    }

    /**
     * Get the contents of a section.
	 * 得到一个节的内容
     *
     * @param  string  $name
     * @param  string|null  $default
     * @return mixed
     */
    public function getSection($name, $default = null)
    {
        return $this->getSections()[$name] ?? $default;
    }

    /**
     * Get the entire array of sections.
	 * 得到整个section数组
     *
     * @return array
     */
    public function getSections()
    {
        return $this->sections;
    }

    /**
     * Flush all of the sections.
	 * 刷新所有的部分
     *
     * @return void
     */
    public function flushSections()
    {
        $this->sections = [];
        $this->sectionStack = [];
    }
}

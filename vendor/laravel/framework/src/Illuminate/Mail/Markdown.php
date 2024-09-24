<?php
/**
 * 邮件编辑器
 */

namespace Illuminate\Mail;

use Illuminate\Contracts\View\Factory as ViewFactory;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Str;
use League\CommonMark\CommonMarkConverter;
use League\CommonMark\Environment;
use League\CommonMark\Extension\Table\TableExtension;
use TijsVerkoyen\CssToInlineStyles\CssToInlineStyles;

class Markdown
{
    /**
     * The view factory implementation.
	 * 视图工厂实现
     *
     * @var \Illuminate\Contracts\View\Factory
     */
    protected $view;

    /**
     * The current theme being used when generating emails.
	 * 生成电子邮件时使用的当前主题
     *
     * @var string
     */
    protected $theme = 'default';

    /**
     * The registered component paths.
	 * 已注册的组件路径
     *
     * @var array
     */
    protected $componentPaths = [];

    /**
     * Create a new Markdown renderer instance.
	 * 创建新的Markdown渲染器实例
     *
     * @param  \Illuminate\Contracts\View\Factory  $view
     * @param  array  $options
     * @return void
     */
    public function __construct(ViewFactory $view, array $options = [])
    {
        $this->view = $view;
        $this->theme = $options['theme'] ?? 'default';
        $this->loadComponentsFrom($options['paths'] ?? []);
    }

    /**
     * Render the Markdown template into HTML.
	 * 呈现Markdown模板为HTML
     *
     * @param  string  $view
     * @param  array  $data
     * @param  \TijsVerkoyen\CssToInlineStyles\CssToInlineStyles|null  $inliner
     * @return \Illuminate\Support\HtmlString
     */
    public function render($view, array $data = [], $inliner = null)
    {
        $this->view->flushFinderCache();

        $contents = $this->view->replaceNamespace(
            'mail', $this->htmlComponentPaths()
        )->make($view, $data)->render();

        $theme = Str::contains($this->theme, '::')
            ? $this->theme
            : 'mail::themes.'.$this->theme;

        return new HtmlString(($inliner ?: new CssToInlineStyles)->convert(
            $contents, $this->view->make($theme, $data)->render()
        ));
    }

    /**
     * Render the Markdown template into text.
	 * 呈现Markdown模板为文本
     *
     * @param  string  $view
     * @param  array  $data
     * @return \Illuminate\Support\HtmlString
     */
    public function renderText($view, array $data = [])
    {
        $this->view->flushFinderCache();

        $contents = $this->view->replaceNamespace(
            'mail', $this->textComponentPaths()
        )->make($view, $data)->render();

        return new HtmlString(
            html_entity_decode(preg_replace("/[\r\n]{2,}/", "\n\n", $contents), ENT_QUOTES, 'UTF-8')
        );
    }

    /**
     * Parse the given Markdown text into HTML.
	 * 将给定的Markdown文本解析为HTML
     *
     * @param  string  $text
     * @return \Illuminate\Support\HtmlString
     */
    public static function parse($text)
    {
        $environment = Environment::createCommonMarkEnvironment();

        $environment->addExtension(new TableExtension);

        $converter = new CommonMarkConverter([
            'allow_unsafe_links' => false,
        ], $environment);

        return new HtmlString($converter->convertToHtml($text));
    }

    /**
     * Get the HTML component paths.
	 * 得到HTML组件路径
     *
     * @return array
     */
    public function htmlComponentPaths()
    {
        return array_map(function ($path) {
            return $path.'/html';
        }, $this->componentPaths());
    }

    /**
     * Get the text component paths.
	 * 得到文本组件路径
     *
     * @return array
     */
    public function textComponentPaths()
    {
        return array_map(function ($path) {
            return $path.'/text';
        }, $this->componentPaths());
    }

    /**
     * Get the component paths.
	 * 得到组件路径
     *
     * @return array
     */
    protected function componentPaths()
    {
        return array_unique(array_merge($this->componentPaths, [
            __DIR__.'/resources/views',
        ]));
    }

    /**
     * Register new mail component paths.
	 * 注册新邮件组件路径
     *
     * @param  array  $paths
     * @return void
     */
    public function loadComponentsFrom(array $paths = [])
    {
        $this->componentPaths = $paths;
    }

    /**
     * Set the default theme to be used.
	 * 设置要使用的默认主题
     *
     * @param  string  $theme
     * @return $this
     */
    public function theme($theme)
    {
        $this->theme = $theme;

        return $this;
    }
}

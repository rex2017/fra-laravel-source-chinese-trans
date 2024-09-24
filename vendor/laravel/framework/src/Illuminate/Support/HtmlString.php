<?php
/**
 * 支持，html字符串
 */

namespace Illuminate\Support;

use Illuminate\Contracts\Support\Htmlable;

class HtmlString implements Htmlable
{
    /**
     * The HTML string.
	 * HTML字符串
     *
     * @var string
     */
    protected $html;

    /**
     * Create a new HTML string instance.
	 * 创建新的HTMl字符串实例
     *
     * @param  string  $html
     * @return void
     */
    public function __construct($html)
    {
        $this->html = $html;
    }

    /**
     * Get the HTML string.
	 * 得到HTML字符串
     *
     * @return string
     */
    public function toHtml()
    {
        return $this->html;
    }

    /**
     * Get the HTML string.
	 * 得到HTML字符串
     *
     * @return string
     */
    public function __toString()
    {
        return $this->toHtml();
    }
}

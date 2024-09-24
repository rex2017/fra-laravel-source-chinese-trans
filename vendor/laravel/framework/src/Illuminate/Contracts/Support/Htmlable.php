<?php
/**
 * 契约，HTML接口
 */

namespace Illuminate\Contracts\Support;

interface Htmlable
{
    /**
     * Get content as a string of HTML.
	 * 得到内容为HTML
     *
     * @return string
     */
    public function toHtml();
}

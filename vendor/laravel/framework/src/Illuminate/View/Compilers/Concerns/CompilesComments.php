<?php
/**
 * 视图，编译评论
 */

namespace Illuminate\View\Compilers\Concerns;

trait CompilesComments
{
    /**
     * Compile Blade comments into an empty string.
	 * 编译Blade注释成一个空字符串
     *
     * @param  string  $value
     * @return string
     */
    protected function compileComments($value)
    {
        $pattern = sprintf('/%s--(.*?)--%s/s', $this->contentTags[0], $this->contentTags[1]);

        return preg_replace($pattern, '', $value);
    }
}

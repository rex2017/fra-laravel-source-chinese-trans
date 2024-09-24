<?php
/**
 * 视图，编译Echo
 */

namespace Illuminate\View\Compilers\Concerns;

trait CompilesEchos
{
    /**
     * Compile Blade echos into valid PHP.
	 * 将Blade回显编译成有效的PHP
     *
     * @param  string  $value
     * @return string
     */
    protected function compileEchos($value)
    {
        foreach ($this->getEchoMethods() as $method) {
            $value = $this->$method($value);
        }

        return $value;
    }

    /**
     * Get the echo methods in the proper order for compilation.
	 * 得到echo方法以进行编译以适当的顺序
     *
     * @return array
     */
    protected function getEchoMethods()
    {
        return [
            'compileRawEchos',
            'compileEscapedEchos',
            'compileRegularEchos',
        ];
    }

    /**
     * Compile the "raw" echo statements.
	 * 编译"原始"echo语句
     *
     * @param  string  $value
     * @return string
     */
    protected function compileRawEchos($value)
    {
        $pattern = sprintf('/(@)?%s\s*(.+?)\s*%s(\r?\n)?/s', $this->rawTags[0], $this->rawTags[1]);

        $callback = function ($matches) {
            $whitespace = empty($matches[3]) ? '' : $matches[3].$matches[3];

            return $matches[1] ? substr($matches[0], 1) : "<?php echo {$matches[2]}; ?>{$whitespace}";
        };

        return preg_replace_callback($pattern, $callback, $value);
    }

    /**
     * Compile the "regular" echo statements.
	 * 编译"常规"echo语句
     *
     * @param  string  $value
     * @return string
     */
    protected function compileRegularEchos($value)
    {
        $pattern = sprintf('/(@)?%s\s*(.+?)\s*%s(\r?\n)?/s', $this->contentTags[0], $this->contentTags[1]);

        $callback = function ($matches) {
            $whitespace = empty($matches[3]) ? '' : $matches[3].$matches[3];

            $wrapped = sprintf($this->echoFormat, $matches[2]);

            return $matches[1] ? substr($matches[0], 1) : "<?php echo {$wrapped}; ?>{$whitespace}";
        };

        return preg_replace_callback($pattern, $callback, $value);
    }

    /**
     * Compile the escaped echo statements.
	 * 编译转义的echo语
     *
     * @param  string  $value
     * @return string
     */
    protected function compileEscapedEchos($value)
    {
        $pattern = sprintf('/(@)?%s\s*(.+?)\s*%s(\r?\n)?/s', $this->escapedTags[0], $this->escapedTags[1]);

        $callback = function ($matches) {
            $whitespace = empty($matches[3]) ? '' : $matches[3].$matches[3];

            return $matches[1] ? $matches[0] : "<?php echo e({$matches[2]}); ?>{$whitespace}";
        };

        return preg_replace_callback($pattern, $callback, $value);
    }
}

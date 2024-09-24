<?php
/**
 * 视图，编译原始php
 */

namespace Illuminate\View\Compilers\Concerns;

trait CompilesRawPhp
{
    /**
     * Compile the raw PHP statements into valid PHP.
	 * 编译原始PHP语句成有效的PHP
     *
     * @param  string  $expression
     * @return string
     */
    protected function compilePhp($expression)
    {
        if ($expression) {
            return "<?php {$expression}; ?>";
        }

        return '@php';
    }

    /**
     * Compile the unset statements into valid PHP.
	 *  编译unset语句成有效的PHP
     *
     * @param  string  $expression
     * @return string
     */
    protected function compileUnset($expression)
    {
        return "<?php unset{$expression}; ?>";
    }
}

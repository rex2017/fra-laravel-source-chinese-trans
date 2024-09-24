<?php
/**
 * 视图，编译注入
 */

namespace Illuminate\View\Compilers\Concerns;

trait CompilesInjections
{
    /**
     * Compile the inject statements into valid PHP.
	 * 编译注入语句为有效的PHP
     *
     * @param  string  $expression
     * @return string
     */
    protected function compileInject($expression)
    {
        $segments = explode(',', preg_replace("/[\(\)\\\"\']/", '', $expression));

        $variable = trim($segments[0]);

        $service = trim($segments[1]);

        return "<?php \${$variable} = app('{$service}'); ?>";
    }
}

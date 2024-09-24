<?php
/**
 * 视图，编译包括
 */

namespace Illuminate\View\Compilers\Concerns;

trait CompilesIncludes
{
    /**
     * Compile the each statements into valid PHP.
	 * 编译每个语句成有效的PHP
     *
     * @param  string  $expression
     * @return string
     */
    protected function compileEach($expression)
    {
        return "<?php echo \$__env->renderEach{$expression}; ?>";
    }

    /**
     * Compile the include statements into valid PHP.
	 * 编译include语句成有效的PHP
     *
     * @param  string  $expression
     * @return string
     */
    protected function compileInclude($expression)
    {
        $expression = $this->stripParentheses($expression);

        return "<?php echo \$__env->make({$expression}, \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>";
    }

    /**
     * Compile the include-if statements into valid PHP.
	 * 编译include-if语句成有效的PHP
     *
     * @param  string  $expression
     * @return string
     */
    protected function compileIncludeIf($expression)
    {
        $expression = $this->stripParentheses($expression);

        return "<?php if (\$__env->exists({$expression})) echo \$__env->make({$expression}, \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>";
    }

    /**
     * Compile the include-when statements into valid PHP.
	 * 编译include-when语句成有效的PHP
     *
     * @param  string  $expression
     * @return string
     */
    protected function compileIncludeWhen($expression)
    {
        $expression = $this->stripParentheses($expression);

        return "<?php echo \$__env->renderWhen($expression, \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path'])); ?>";
    }

    /**
     * Compile the include-unless statements into valid PHP.
	 * 编译include-unless语句成有效的PHP
     *
     * @param  string  $expression
     * @return string
     */
    protected function compileIncludeUnless($expression)
    {
        $expression = $this->stripParentheses($expression);

        return "<?php echo \$__env->renderWhen(! $expression, \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path'])); ?>";
    }

    /**
     * Compile the include-first statements into valid PHP.
	 * 编译include-first语句成有效的PHP
     *
     * @param  string  $expression
     * @return string
     */
    protected function compileIncludeFirst($expression)
    {
        $expression = $this->stripParentheses($expression);

        return "<?php echo \$__env->first({$expression}, \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>";
    }
}

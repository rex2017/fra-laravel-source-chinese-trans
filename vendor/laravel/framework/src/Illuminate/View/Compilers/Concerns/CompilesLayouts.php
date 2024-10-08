<?php
/**
 * 视图，编译布局
 */

namespace Illuminate\View\Compilers\Concerns;

trait CompilesLayouts
{
    /**
     * The name of the last section that was started.
	 * 最后开始的部分的名称
     *
     * @var string
     */
    protected $lastSection;

    /**
     * Compile the extends statements into valid PHP.
	 * 编译extends语句成有效的PHP
     *
     * @param  string  $expression
     * @return string
     */
    protected function compileExtends($expression)
    {
        $expression = $this->stripParentheses($expression);

        $echo = "<?php echo \$__env->make({$expression}, \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>";

        $this->footer[] = $echo;

        return '';
    }

    /**
     * Compile the section statements into valid PHP.
	 * 编译section语句成有效的PHP
     *
     * @param  string  $expression
     * @return string
     */
    protected function compileSection($expression)
    {
        $this->lastSection = trim($expression, "()'\" ");

        return "<?php \$__env->startSection{$expression}; ?>";
    }

    /**
     * Replace the @parent directive to a placeholder.
	 * 替换@parent指令为占位符
     *
     * @return string
     */
    protected function compileParent()
    {
        $escapedLastSection = strtr($this->lastSection, ['\\' => '\\\\', "'" => "\\'"]);

        return "<?php echo \Illuminate\View\Factory::parentPlaceholder('{$escapedLastSection}'); ?>";
    }

    /**
     * Compile the yield statements into valid PHP.
	 * 编译yield语句成有效的PHP
     *
     * @param  string  $expression
     * @return string
     */
    protected function compileYield($expression)
    {
        return "<?php echo \$__env->yieldContent{$expression}; ?>";
    }

    /**
     * Compile the show statements into valid PHP.
	 * 编译show语句成有效的PHP
     *
     * @return string
     */
    protected function compileShow()
    {
        return '<?php echo $__env->yieldSection(); ?>';
    }

    /**
     * Compile the append statements into valid PHP.
	 * 编译append语句成有效的PHP
     *
     * @return string
     */
    protected function compileAppend()
    {
        return '<?php $__env->appendSection(); ?>';
    }

    /**
     * Compile the overwrite statements into valid PHP.
	 * 编译overwrite语句成有效的PHP
     *
     * @return string
     */
    protected function compileOverwrite()
    {
        return '<?php $__env->stopSection(true); ?>';
    }

    /**
     * Compile the stop statements into valid PHP.
	 * 编译stop语句成有效的PHP
     *
     * @return string
     */
    protected function compileStop()
    {
        return '<?php $__env->stopSection(); ?>';
    }

    /**
     * Compile the end-section statements into valid PHP.
	 * 编译end-section语句成有效的PHP
     *
     * @return string
     */
    protected function compileEndsection()
    {
        return '<?php $__env->stopSection(); ?>';
    }
}

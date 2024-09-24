<?php
/**
 * 视图，编译组件
 */

namespace Illuminate\View\Compilers\Concerns;

trait CompilesComponents
{
    /**
     * Compile the component statements into valid PHP.
	 * 编译组件语句为有效的PHP
     *
     * @param  string  $expression
     * @return string
     */
    protected function compileComponent($expression)
    {
        return "<?php \$__env->startComponent{$expression}; ?>";
    }

    /**
     * Compile the end-component statements into valid PHP.
	 * 编译最终组件语句成有效的PHP
     *
     * @return string
     */
    protected function compileEndComponent()
    {
        return '<?php echo $__env->renderComponent(); ?>';
    }

    /**
     * Compile the slot statements into valid PHP.
	 * 编译slot语句成有效的PHP
     *
     * @param  string  $expression
     * @return string
     */
    protected function compileSlot($expression)
    {
        return "<?php \$__env->slot{$expression}; ?>";
    }

    /**
     * Compile the end-slot statements into valid PHP.
	 * 编译end-slot语句成有效的PHP
     *
     * @return string
     */
    protected function compileEndSlot()
    {
        return '<?php $__env->endSlot(); ?>';
    }

    /**
     * Compile the component-first statements into valid PHP.
	 * 编译component-first语句成有效的PHP
     *
     * @param  string  $expression
     * @return string
     */
    protected function compileComponentFirst($expression)
    {
        return "<?php \$__env->startComponentFirst{$expression}; ?>";
    }

    /**
     * Compile the end-component-first statements into valid PHP.
	 * 编译end-component-first语句成有效的PHP
     *
     * @return string
     */
    protected function compileEndComponentFirst()
    {
        return $this->compileEndComponent();
    }
}

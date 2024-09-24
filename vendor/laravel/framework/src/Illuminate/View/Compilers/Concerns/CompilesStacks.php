<?php
/**
 * 视图，编译栈
 */

namespace Illuminate\View\Compilers\Concerns;

trait CompilesStacks
{
    /**
     * Compile the stack statements into the content.
	 * 编译stack语句成有效的PHP
     *
     * @param  string  $expression
     * @return string
     */
    protected function compileStack($expression)
    {
        return "<?php echo \$__env->yieldPushContent{$expression}; ?>";
    }

    /**
     * Compile the push statements into valid PHP.
	 * 编译push语句成有效的PHP
     *
     * @param  string  $expression
     * @return string
     */
    protected function compilePush($expression)
    {
        return "<?php \$__env->startPush{$expression}; ?>";
    }

    /**
     * Compile the end-push statements into valid PHP.
	 * 编译end-push语句成有效的PHP
     *
     * @return string
     */
    protected function compileEndpush()
    {
        return '<?php $__env->stopPush(); ?>';
    }

    /**
     * Compile the prepend statements into valid PHP.
	 * 编译prepend语句成有效的PHP
     *
     * @param  string  $expression
     * @return string
     */
    protected function compilePrepend($expression)
    {
        return "<?php \$__env->startPrepend{$expression}; ?>";
    }

    /**
     * Compile the end-prepend statements into valid PHP.
	 * 编译end-prepend语句成有效的PHP
     *
     * @return string
     */
    protected function compileEndprepend()
    {
        return '<?php $__env->stopPrepend(); ?>';
    }
}

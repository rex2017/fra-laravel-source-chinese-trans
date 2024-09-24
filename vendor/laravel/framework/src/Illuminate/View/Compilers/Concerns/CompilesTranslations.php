<?php
/**
 * 视图，编译翻译
 */

namespace Illuminate\View\Compilers\Concerns;

trait CompilesTranslations
{
    /**
     * Compile the lang statements into valid PHP.
	 * 编译lang语句为有效的PHP
     *
     * @param  string  $expression
     * @return string
     */
    protected function compileLang($expression)
    {
        if (is_null($expression)) {
            return '<?php $__env->startTranslation(); ?>';
        } elseif ($expression[1] === '[') {
            return "<?php \$__env->startTranslation{$expression}; ?>";
        }

        return "<?php echo app('translator')->get{$expression}; ?>";
    }

    /**
     * Compile the end-lang statements into valid PHP.
	 * 编译end-lang语句成有效的PHP
     *
     * @return string
     */
    protected function compileEndlang()
    {
        return '<?php echo $__env->renderTranslation(); ?>';
    }

    /**
     * Compile the choice statements into valid PHP.
	 * 编译choice语句成有效的PHP
     *
     * @param  string  $expression
     * @return string
     */
    protected function compileChoice($expression)
    {
        return "<?php echo app('translator')->choice{$expression}; ?>";
    }
}

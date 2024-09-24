<?php
/**
 * 视图，编译授权
 */

namespace Illuminate\View\Compilers\Concerns;

trait CompilesAuthorizations
{
    /**
     * Compile the can statements into valid PHP.
	 * 编译can语句为有效的PHP
     *
     * @param  string  $expression
     * @return string
     */
    protected function compileCan($expression)
    {
        return "<?php if (app(\Illuminate\\Contracts\\Auth\\Access\\Gate::class)->check{$expression}): ?>";
    }

    /**
     * Compile the cannot statements into valid PHP.
	 * 编译cannot语句成有效的PHP
     *
     * @param  string  $expression
     * @return string
     */
    protected function compileCannot($expression)
    {
        return "<?php if (app(\Illuminate\\Contracts\\Auth\\Access\\Gate::class)->denies{$expression}): ?>";
    }

    /**
     * Compile the canany statements into valid PHP.
	 * 编译canany语句成有效的PHP
     *
     * @param  string  $expression
     * @return string
     */
    protected function compileCanany($expression)
    {
        return "<?php if (app(\Illuminate\\Contracts\\Auth\\Access\\Gate::class)->any{$expression}): ?>";
    }

    /**
     * Compile the else-can statements into valid PHP.
	 * 编译else-can语句成有效的PHP
     *
     * @param  string  $expression
     * @return string
     */
    protected function compileElsecan($expression)
    {
        return "<?php elseif (app(\Illuminate\\Contracts\\Auth\\Access\\Gate::class)->check{$expression}): ?>";
    }

    /**
     * Compile the else-cannot statements into valid PHP.
	 * 编译else-cannot语句成有效的PHP
     *
     * @param  string  $expression
     * @return string
     */
    protected function compileElsecannot($expression)
    {
        return "<?php elseif (app(\Illuminate\\Contracts\\Auth\\Access\\Gate::class)->denies{$expression}): ?>";
    }

    /**
     * Compile the else-canany statements into valid PHP.
	 * 编译else-canany语句成有效的PHP
     *
     * @param  string  $expression
     * @return string
     */
    protected function compileElsecanany($expression)
    {
        return "<?php elseif (app(\Illuminate\\Contracts\\Auth\\Access\\Gate::class)->any{$expression}): ?>";
    }

    /**
     * Compile the end-can statements into valid PHP.
	 * 编译end-can语句成有效的PHP
     *
     * @return string
     */
    protected function compileEndcan()
    {
        return '<?php endif; ?>';
    }

    /**
     * Compile the end-cannot statements into valid PHP.
	 * 编译end-cannot语句成有效的PHP
     *
     * @return string
     */
    protected function compileEndcannot()
    {
        return '<?php endif; ?>';
    }

    /**
     * Compile the end-canany statements into valid PHP.
	 * 编译end-canany语句成有效的PHP
     *
     * @return string
     */
    protected function compileEndcanany()
    {
        return '<?php endif; ?>';
    }
}

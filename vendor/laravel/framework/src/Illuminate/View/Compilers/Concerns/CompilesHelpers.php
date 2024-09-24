<?php
/**
 * 视图，编译帮助
 */

namespace Illuminate\View\Compilers\Concerns;

trait CompilesHelpers
{
    /**
     * Compile the CSRF statements into valid PHP.
	 * 将CSRF语句编译成有效的PHP
     *
     * @return string
     */
    protected function compileCsrf()
    {
        return '<?php echo csrf_field(); ?>';
    }

    /**
     * Compile the "dd" statements into valid PHP.
	 * 编译"dd"语句成有效的PHP
     *
     * @param  string  $arguments
     * @return string
     */
    protected function compileDd($arguments)
    {
        return "<?php dd{$arguments}; ?>";
    }

    /**
     * Compile the "dump" statements into valid PHP.
	 * 编译"dump"语句成有效的PHP
     *
     * @param  string  $arguments
     * @return string
     */
    protected function compileDump($arguments)
    {
        return "<?php dump{$arguments}; ?>";
    }

    /**
     * Compile the method statements into valid PHP.
	 * 编译方法语句成有效的PHP
     *
     * @param  string  $method
     * @return string
     */
    protected function compileMethod($method)
    {
        return "<?php echo method_field{$method}; ?>";
    }
}

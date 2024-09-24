<?php
/**
 * 视图，编译器接口
 */

namespace Illuminate\View\Compilers;

interface CompilerInterface
{
    /**
     * Get the path to the compiled version of a view.
	 * 得到视图的编译版本的路径
     *
     * @param  string  $path
     * @return string
     */
    public function getCompiledPath($path);

    /**
     * Determine if the given view is expired.
	 * 确定给定的视图是否过期
     *
     * @param  string  $path
     * @return bool
     */
    public function isExpired($path);

    /**
     * Compile the view at the given path.
	 * 编译视图在给定路径
     *
     * @param  string  $path
     * @return void
     */
    public function compile($path);
}

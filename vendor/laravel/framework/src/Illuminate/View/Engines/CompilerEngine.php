<?php
/**
 * 视图，编译引擎
 */

namespace Illuminate\View\Engines;

use Exception;
use Illuminate\View\Compilers\CompilerInterface;
use Illuminate\View\ViewException;

class CompilerEngine extends PhpEngine
{
    /**
     * The Blade compiler instance.
	 * Blade编译器实例
     *
     * @var \Illuminate\View\Compilers\CompilerInterface
     */
    protected $compiler;

    /**
     * A stack of the last compiled templates.
	 * 最后编译的模板的堆栈
     *
     * @var array
     */
    protected $lastCompiled = [];

    /**
     * Create a new Blade view engine instance.
	 * 创建新的Blade视图引擎实例
     *
     * @param  \Illuminate\View\Compilers\CompilerInterface  $compiler
     * @return void
     */
    public function __construct(CompilerInterface $compiler)
    {
        $this->compiler = $compiler;
    }

    /**
     * Get the evaluated contents of the view.
	 * 得到视图的求值内容
     *
     * @param  string  $path
     * @param  array  $data
     * @return string
     */
    public function get($path, array $data = [])
    {
        $this->lastCompiled[] = $path;

        // If this given view has expired, which means it has simply been edited since
        // it was last compiled, we will re-compile the views so we can evaluate a
        // fresh copy of the view. We'll pass the compiler the path of the view.
		// 如果此给定视图已过期，这意味着自上次编译以来它只是被编辑过，我们将重新编译视图，
		// 以便评估视图的新副本。我们将把视图的路径传递给编译器。
        if ($this->compiler->isExpired($path)) {
            $this->compiler->compile($path);
        }

        $compiled = $this->compiler->getCompiledPath($path);

        // Once we have the path to the compiled file, we will evaluate the paths with
        // typical PHP just like any other templates. We also keep a stack of views
        // which have been rendered for right exception messages to be generated.
		// 一旦我们有了编译文件的路径，我们将像其他模板一样使用典型的PHP来评估路径。
		// 我们还保留了一组视图，这些视图已被渲染以生成正确的异常消息。
        $results = $this->evaluatePath($compiled, $data);

        array_pop($this->lastCompiled);

        return $results;
    }

    /**
     * Handle a view exception.
	 * 处理视图异常
     *
     * @param  \Exception  $e
     * @param  int  $obLevel
     * @return void
     *
     * @throws \Exception
     */
    protected function handleViewException(Exception $e, $obLevel)
    {
        $e = new ViewException($this->getMessage($e), 0, 1, $e->getFile(), $e->getLine(), $e);

        parent::handleViewException($e, $obLevel);
    }

    /**
     * Get the exception message for an exception.
	 * 得到异常的异常消息
     *
     * @param  \Exception  $e
     * @return string
     */
    protected function getMessage(Exception $e)
    {
        return $e->getMessage().' (View: '.realpath(last($this->lastCompiled)).')';
    }

    /**
     * Get the compiler implementation.
	 * 得到编译器实现
     *
     * @return \Illuminate\View\Compilers\CompilerInterface
     */
    public function getCompiler()
    {
        return $this->compiler;
    }
}

<?php
/**
 * 视图，PHP引擎
 */

namespace Illuminate\View\Engines;

use Exception;
use Illuminate\Contracts\View\Engine;
use Symfony\Component\Debug\Exception\FatalThrowableError;
use Throwable;

class PhpEngine implements Engine
{
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
        return $this->evaluatePath($path, $data);
    }

    /**
     * Get the evaluated contents of the view at the given path.
	 * 得到给定路径上的视图的求值内容
     *
     * @param  string  $__path
     * @param  array  $__data
     * @return string
     */
    protected function evaluatePath($__path, $__data)
    {
        $obLevel = ob_get_level();

        ob_start();

        extract($__data, EXTR_SKIP);

        // We'll evaluate the contents of the view inside a try/catch block so we can
        // flush out any stray output that might get out before an error occurs or
        // an exception is thrown. This prevents any partial views from leaking.
		// 我们将在try/catch块中评估视图的内容，以便在发生错误或抛出异常之前清除可能出现的任何杂散输出。
		// 这可以防止任何局部视图泄漏。
        try {
            include $__path;
        } catch (Exception $e) {
            $this->handleViewException($e, $obLevel);
        } catch (Throwable $e) {
            $this->handleViewException(new FatalThrowableError($e), $obLevel);
        }

        return ltrim(ob_get_clean());
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
        while (ob_get_level() > $obLevel) {
            ob_end_clean();
        }

        throw $e;
    }
}

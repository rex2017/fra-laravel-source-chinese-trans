<?php
/**
 * 视图异常
 */

namespace Illuminate\View;

use ErrorException;
use Illuminate\Container\Container;
use Illuminate\Support\Reflector;

class ViewException extends ErrorException
{
    /**
     * Report the exception.
	 * 报告异常
     *
     * @return bool|null
     */
    public function report()
    {
        $exception = $this->getPrevious();

        if (Reflector::isCallable($reportCallable = [$exception, 'report'])) {
            return Container::getInstance()->call($reportCallable);
        }

        return false;
    }

    /**
     * Render the exception into an HTTP response.
	 * 呈现异常至HTTP响应
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function render($request)
    {
        $exception = $this->getPrevious();

        if ($exception && method_exists($exception, 'render')) {
            return $exception->render($request);
        }
    }
}

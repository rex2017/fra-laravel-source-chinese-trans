<?php
/**
 * 契约，调试异常处理接口
 */

namespace Illuminate\Contracts\Debug;

use Exception;

interface ExceptionHandler
{
    /**
     * Report or log an exception.
	 * 报告或记录异常
     *
     * @param  \Exception  $e
     * @return void
     *
     * @throws \Exception
     */
    public function report(Exception $e);

    /**
     * Determine if the exception should be reported.
	 * 确定是否应该报告异常
     *
     * @param  \Exception  $e
     * @return bool
     */
    public function shouldReport(Exception $e);

    /**
     * Render an exception into an HTTP response.
	 * 呈现异常至HTTP响应中
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Exception  $e
     * @return \Symfony\Component\HttpFoundation\Response
     *
     * @throws \Exception
     */
    public function render($request, Exception $e);

    /**
     * Render an exception to the console.
	 * 呈现异常至控制台
     *
     * @param  \Symfony\Component\Console\Output\OutputInterface  $output
     * @param  \Exception  $e
     * @return void
     */
    public function renderForConsole($output, Exception $e);
}

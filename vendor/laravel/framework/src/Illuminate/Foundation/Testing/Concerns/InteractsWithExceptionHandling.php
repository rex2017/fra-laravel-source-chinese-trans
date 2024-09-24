<?php
/**
 * 基础，与异常处理交互
 */

namespace Illuminate\Foundation\Testing\Concerns;

use Exception;
use Illuminate\Contracts\Debug\ExceptionHandler;
use Illuminate\Validation\ValidationException;
use Symfony\Component\Console\Application as ConsoleApplication;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

trait InteractsWithExceptionHandling
{
    /**
     * The original exception handler.
	 * 原始异常处理
     *
     * @var \Illuminate\Contracts\Debug\ExceptionHandler|null
     */
    protected $originalExceptionHandler;

    /**
     * Restore exception handling.
	 * 恢复异常处理
     *
     * @return $this
     */
    protected function withExceptionHandling()
    {
        if ($this->originalExceptionHandler) {
            $this->app->instance(ExceptionHandler::class, $this->originalExceptionHandler);
        }

        return $this;
    }

    /**
     * Only handle the given exceptions via the exception handler.
	 * 只通过异常处理程序处理给定的异常
     *
     * @param  array  $exceptions
     * @return $this
     */
    protected function handleExceptions(array $exceptions)
    {
        return $this->withoutExceptionHandling($exceptions);
    }

    /**
     * Only handle validation exceptions via the exception handler.
	 * 只通过异常处理程序处理验证异常
     *
     * @return $this
     */
    protected function handleValidationExceptions()
    {
        return $this->handleExceptions([ValidationException::class]);
    }

    /**
     * Disable exception handling for the test.
	 * 禁用测试的异常处理
     *
     * @param  array  $except
     * @return $this
     */
    protected function withoutExceptionHandling(array $except = [])
    {
        if ($this->originalExceptionHandler == null) {
            $this->originalExceptionHandler = app(ExceptionHandler::class);
        }

        $this->app->instance(ExceptionHandler::class, new class($this->originalExceptionHandler, $except) implements ExceptionHandler
        {
            protected $except;
            protected $originalHandler;

            /**
             * Create a new class instance.
             *
             * @param  \Illuminate\Contracts\Debug\ExceptionHandler  $originalHandler
             * @param  array  $except
             * @return void
             */
            public function __construct($originalHandler, $except = [])
            {
                $this->except = $except;
                $this->originalHandler = $originalHandler;
            }

            /**
             * Report or log an exception.
             *
             * @param  \Exception  $e
             * @return void
             *
             * @throws \Exception
             */
            public function report(Exception $e)
            {
                //
            }

            /**
             * Determine if the exception should be reported.
             *
             * @param  \Exception  $e
             * @return bool
             */
            public function shouldReport(Exception $e)
            {
                return false;
            }

            /**
             * Render an exception into an HTTP response.
             *
             * @param  \Illuminate\Http\Request  $request
             * @param  \Exception  $e
             * @return \Symfony\Component\HttpFoundation\Response
             *
             * @throws \Exception
             */
            public function render($request, Exception $e)
            {
                foreach ($this->except as $class) {
                    if ($e instanceof $class) {
                        return $this->originalHandler->render($request, $e);
                    }
                }

                if ($e instanceof NotFoundHttpException) {
                    throw new NotFoundHttpException(
                        "{$request->method()} {$request->url()}", null, $e->getCode()
                    );
                }

                throw $e;
            }

            /**
             * Render an exception to the console.
             *
             * @param  \Symfony\Component\Console\Output\OutputInterface  $output
             * @param  \Exception  $e
             * @return void
             */
            public function renderForConsole($output, Exception $e)
            {
                (new ConsoleApplication)->renderException($e, $output);
            }
        });

        return $this;
    }
}

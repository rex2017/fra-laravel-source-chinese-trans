<?php
/**
 * 路由管道
 */

namespace Illuminate\Routing;

use Exception;
use Illuminate\Contracts\Debug\ExceptionHandler;
use Illuminate\Contracts\Support\Responsable;
use Illuminate\Http\Request;
use Illuminate\Pipeline\Pipeline as BasePipeline;

/**
 * This extended pipeline catches any exceptions that occur during each slice.
 * 这个扩展的管道捕获每个切片期间发生的任何异常
 *
 * The exceptions are converted to HTTP responses for proper middleware handling.
 */
class Pipeline extends BasePipeline
{
    /**
     * Handles the value returned from each pipe before passing it to the next.
	 * 处理从每个管道返回的值，然后将其传递给下一个管道
     *
     * @param  mixed  $carry
     * @return mixed
     */
    protected function handleCarry($carry)
    {
        return $carry instanceof Responsable
            ? $carry->toResponse($this->getContainer()->make(Request::class))
            : $carry;
    }

    /**
     * Handle the given exception.
	 * 处理给定的异常
     *
     * @param  mixed  $passable
     * @param  \Exception  $e
     * @return mixed
     *
     * @throws \Exception
     */
    protected function handleException($passable, Exception $e)
    {
        if (! $this->container->bound(ExceptionHandler::class) ||
            ! $passable instanceof Request) {
            throw $e;
        }

        $handler = $this->container->make(ExceptionHandler::class);

        $handler->report($e);

        $response = $handler->render($passable, $e);

        if (is_object($response) && method_exists($response, 'withException')) {
            $response->withException($e);
        }

        return $response;
    }
}

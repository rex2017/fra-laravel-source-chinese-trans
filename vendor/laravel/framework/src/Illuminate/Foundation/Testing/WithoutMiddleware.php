<?php
/**
 * 基础，不使用中间件
 */

namespace Illuminate\Foundation\Testing;

use Exception;

trait WithoutMiddleware
{
    /**
     * Prevent all middleware from being executed for this test class.
	 * 防止为此测试类执行所有中间件
     *
     * @throws \Exception
     */
    public function disableMiddlewareForAllTests()
    {
        if (method_exists($this, 'withoutMiddleware')) {
            $this->withoutMiddleware();
        } else {
            throw new Exception('Unable to disable middleware. MakesHttpRequests trait not used.');
        }
    }
}

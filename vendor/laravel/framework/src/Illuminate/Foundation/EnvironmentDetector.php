<?php
/**
 * 基础，环境检查
 */

namespace Illuminate\Foundation;

use Closure;
use Illuminate\Support\Str;

class EnvironmentDetector
{
    /**
     * Detect the application's current environment.
	 * 检查应用的当前环境
     *
     * @param  \Closure  $callback
     * @param  array|null  $consoleArgs
     * @return string
     */
    public function detect(Closure $callback, $consoleArgs = null)
    {
        if ($consoleArgs) {
            return $this->detectConsoleEnvironment($callback, $consoleArgs);
        }

        return $this->detectWebEnvironment($callback);
    }

    /**
     * Set the application environment for a web request.
	 * 设置web请求环境
     *
     * @param  \Closure  $callback
     * @return string
     */
    protected function detectWebEnvironment(Closure $callback)
    {
        return $callback();
    }

    /**
     * Set the application environment from command-line arguments.
	 * 设置控制台环境
     *
     * @param  \Closure  $callback
     * @param  array  $args
     * @return string
     */
    protected function detectConsoleEnvironment(Closure $callback, array $args)
    {
        // First we will check if an environment argument was passed via console arguments
        // and if it was that automatically overrides as the environment. Otherwise, we
        // will check the environment as a "web" request like a typical HTTP request.
		// 首先我们将检查环境参数是否通过控制台参数传递，如果是这样它将会自己覆盖环境。
		// 否则，我们将当作web请求进行环境检查。
        if (! is_null($value = $this->getEnvironmentArgument($args))) {
            return $value;
        }

        return $this->detectWebEnvironment($callback);
    }

    /**
     * Get the environment argument from the console.
	 * 得到环境参数从控制台
     *
     * @param  array  $args
     * @return string|null
     */
    protected function getEnvironmentArgument(array $args)
    {
        foreach ($args as $i => $value) {
            if ($value === '--env') {
                return $args[$i + 1] ?? null;
            }

            if (Str::startsWith($value, '--env')) {
                return head(array_slice(explode('=', $value), 1));
            }
        }
    }
}

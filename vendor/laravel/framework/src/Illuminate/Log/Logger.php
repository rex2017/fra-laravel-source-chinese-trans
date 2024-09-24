<?php
/**
 * 日志记录器
 */

namespace Illuminate\Log;

use Closure;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Contracts\Support\Jsonable;
use Illuminate\Log\Events\MessageLogged;
use Psr\Log\LoggerInterface;
use RuntimeException;

class Logger implements LoggerInterface
{
    /**
     * The underlying logger implementation.
	 * 底层记录接口
     *
     * @var \Psr\Log\LoggerInterface
     */
    protected $logger;

    /**
     * The event dispatcher instance.
	 * 事件调度实例
     *
     * @var \Illuminate\Contracts\Events\Dispatcher|null
     */
    protected $dispatcher;

    /**
     * Create a new log writer instance.
	 * 创建新的日志写实例
     *
     * @param  \Psr\Log\LoggerInterface  $logger
     * @param  \Illuminate\Contracts\Events\Dispatcher|null  $dispatcher
     * @return void
     */
    public function __construct(LoggerInterface $logger, Dispatcher $dispatcher = null)
    {
        $this->logger = $logger;
        $this->dispatcher = $dispatcher;
    }

    /**
     * Log an emergency message to the logs.
	 * 记录紧急消息至日志
     *
     * @param  string  $message
     * @param  array  $context
     * @return void
     */
    public function emergency($message, array $context = [])
    {
        $this->writeLog(__FUNCTION__, $message, $context);
    }

    /**
     * Log an alert message to the logs.
	 * 记录警告信息至日志
     *
     * @param  string  $message
     * @param  array  $context
     * @return void
     */
    public function alert($message, array $context = [])
    {
        $this->writeLog(__FUNCTION__, $message, $context);
    }

    /**
     * Log a critical message to the logs.
	 * 记录关键信息至日志
     *
     * @param  string  $message
     * @param  array  $context
     * @return void
     */
    public function critical($message, array $context = [])
    {
        $this->writeLog(__FUNCTION__, $message, $context);
    }

    /**
     * Log an error message to the logs.
	 * 记录错误信息至日志
     *
     * @param  string  $message
     * @param  array  $context
     * @return void
     */
    public function error($message, array $context = [])
    {
        $this->writeLog(__FUNCTION__, $message, $context);
    }

    /**
     * Log a warning message to the logs.
	 * 记录警告信息至日志
     *
     * @param  string  $message
     * @param  array  $context
     * @return void
     */
    public function warning($message, array $context = [])
    {
        $this->writeLog(__FUNCTION__, $message, $context);
    }

    /**
     * Log a notice to the logs.
	 * 记录提醒信息至日志
     *
     * @param  string  $message
     * @param  array  $context
     * @return void
     */
    public function notice($message, array $context = [])
    {
        $this->writeLog(__FUNCTION__, $message, $context);
    }

    /**
     * Log an informational message to the logs.
	 * 记录信息至日志
     *
     * @param  string  $message
     * @param  array  $context
     * @return void
     */
    public function info($message, array $context = [])
    {
        $this->writeLog(__FUNCTION__, $message, $context);
    }

    /**
     * Log a debug message to the logs.
	 * 记录调试信息至日志
     *
     * @param  string  $message
     * @param  array  $context
     * @return void
     */
    public function debug($message, array $context = [])
    {
        $this->writeLog(__FUNCTION__, $message, $context);
    }

    /**
     * Log a message to the logs.
	 * 记录信息至日志
     *
     * @param  string  $level
     * @param  string  $message
     * @param  array  $context
     * @return void
     */
    public function log($level, $message, array $context = [])
    {
        $this->writeLog($level, $message, $context);
    }

    /**
     * Dynamically pass log calls into the writer.
	 * 动态地将日志调用传递给写入器
     *
     * @param  string  $level
     * @param  string  $message
     * @param  array  $context
     * @return void
     */
    public function write($level, $message, array $context = [])
    {
        $this->writeLog($level, $message, $context);
    }

    /**
     * Write a message to the log.
	 * 写日志
     *
     * @param  string  $level
     * @param  string  $message
     * @param  array  $context
     * @return void
     */
    protected function writeLog($level, $message, $context)
    {
        $this->fireLogEvent($level, $message = $this->formatMessage($message), $context);

        $this->logger->{$level}($message, $context);
    }

    /**
     * Register a new callback handler for when a log event is triggered.
	 * 注册新的回调处理程序当日志事件触发时
     *
     * @param  \Closure  $callback
     * @return void
     *
     * @throws \RuntimeException
     */
    public function listen(Closure $callback)
    {
        if (! isset($this->dispatcher)) {
            throw new RuntimeException('Events dispatcher has not been set.');
        }

        $this->dispatcher->listen(MessageLogged::class, $callback);
    }

    /**
     * Fires a log event.
	 * 触发一个日志事件
     *
     * @param  string  $level
     * @param  string  $message
     * @param  array  $context
     * @return void
     */
    protected function fireLogEvent($level, $message, array $context = [])
    {
        // If the event dispatcher is set, we will pass along the parameters to the
        // log listeners. These are useful for building profilers or other tools
        // that aggregate all of the log messages for a given "request" cycle.
		// 如果设置了事件调度器，我们将把参数传递给日志监听器。
		// 这些对于构建分析器或其他工具很有用
        if (isset($this->dispatcher)) {
            $this->dispatcher->dispatch(new MessageLogged($level, $message, $context));
        }
    }

    /**
     * Format the parameters for the logger.
	 * 格式化日志
     *
     * @param  mixed  $message
     * @return mixed
     */
    protected function formatMessage($message)
    {
        if (is_array($message)) {
            return var_export($message, true);
        } elseif ($message instanceof Jsonable) {
            return $message->toJson();
        } elseif ($message instanceof Arrayable) {
            return var_export($message->toArray(), true);
        }

        return $message;
    }

    /**
     * Get the underlying logger implementation.
	 * 得到底层日志记录器实现
     *
     * @return \Psr\Log\LoggerInterface
     */
    public function getLogger()
    {
        return $this->logger;
    }

    /**
     * Get the event dispatcher instance.
	 * 得到事件调度实例
     *
     * @return \Illuminate\Contracts\Events\Dispatcher
     */
    public function getEventDispatcher()
    {
        return $this->dispatcher;
    }

    /**
     * Set the event dispatcher instance.
	 * 设置事件调度实例
     *
     * @param  \Illuminate\Contracts\Events\Dispatcher  $dispatcher
     * @return void
     */
    public function setEventDispatcher(Dispatcher $dispatcher)
    {
        $this->dispatcher = $dispatcher;
    }

    /**
     * Dynamically proxy method calls to the underlying logger.
	 * 动态代理方法调用至底层日志
     *
     * @param  string  $method
     * @param  array  $parameters
     * @return mixed
     */
    public function __call($method, $parameters)
    {
        return $this->logger->{$method}(...$parameters);
    }
}

<?php
/**
 * 总线调度
 */

namespace Illuminate\Bus;

use Closure;
use Illuminate\Contracts\Bus\QueueingDispatcher;
use Illuminate\Contracts\Container\Container;
use Illuminate\Contracts\Queue\Queue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Pipeline\Pipeline;
use RuntimeException;

class Dispatcher implements QueueingDispatcher
{
    /**
     * The container implementation.
	 * 容器实现
     *
     * @var \Illuminate\Contracts\Container\Container
     */
    protected $container;

    /**
     * The pipeline instance for the bus.
	 * 总线的管道实例
     *
     * @var \Illuminate\Pipeline\Pipeline
     */
    protected $pipeline;

    /**
     * The pipes to send commands through before dispatching.
	 * 管道发送命令在调度之前
     *
     * @var array
     */
    protected $pipes = [];

    /**
     * The command to handler mapping for non-self-handling events.
	 * 处理命令非自处理事件
     *
     * @var array
     */
    protected $handlers = [];

    /**
     * The queue resolver callback.
	 * 队列解析器回调
     *
     * @var \Closure|null
     */
    protected $queueResolver;

    /**
     * Create a new command dispatcher instance.
	 * 创建新的命令调度实例
     *
     * @param  \Illuminate\Contracts\Container\Container  $container
     * @param  \Closure|null  $queueResolver
     * @return void
     */
    public function __construct(Container $container, Closure $queueResolver = null)
    {
        $this->container = $container;
        $this->queueResolver = $queueResolver;
        $this->pipeline = new Pipeline($container);
    }

    /**
     * Dispatch a command to its appropriate handler.
	 * 分派命令给相应的处理程序
     *
     * @param  mixed  $command
     * @return mixed
     */
    public function dispatch($command)
    {
        if ($this->queueResolver && $this->commandShouldBeQueued($command)) {
            return $this->dispatchToQueue($command);
        }

        return $this->dispatchNow($command);
    }

    /**
     * Dispatch a command to its appropriate handler in the current process.
	 * 分派命令给当前进程中相应的处理程序
     *
     * @param  mixed  $command
     * @param  mixed  $handler
     * @return mixed
     */
    public function dispatchNow($command, $handler = null)
    {
        if ($handler || $handler = $this->getCommandHandler($command)) {
            $callback = function ($command) use ($handler) {
                return $handler->handle($command);
            };
        } else {
            $callback = function ($command) {
                return $this->container->call([$command, 'handle']);
            };
        }

        return $this->pipeline->send($command)->through($this->pipes)->then($callback);
    }

    /**
     * Determine if the given command has a handler.
	 * 判断命令是否有处理程序
     *
     * @param  mixed  $command
     * @return bool
     */
    public function hasCommandHandler($command)
    {
        return array_key_exists(get_class($command), $this->handlers);
    }

    /**
     * Retrieve the handler for a command.
	 * 检索命令的处理程序
     *
     * @param  mixed  $command
     * @return bool|mixed
     */
    public function getCommandHandler($command)
    {
        if ($this->hasCommandHandler($command)) {
            return $this->container->make($this->handlers[get_class($command)]);
        }

        return false;
    }

    /**
     * Determine if the given command should be queued.
	 * 确定是否应该对给定的命令进行排队
     *
     * @param  mixed  $command
     * @return bool
     */
    protected function commandShouldBeQueued($command)
    {
        return $command instanceof ShouldQueue;
    }

    /**
     * Dispatch a command to its appropriate handler behind a queue.
	 * 分派命令给队列后面相应的处理程序
     *
     * @param  mixed  $command
     * @return mixed
     */
    public function dispatchToQueue($command)
    {
        $connection = $command->connection ?? null;

        $queue = call_user_func($this->queueResolver, $connection);

        if (! $queue instanceof Queue) {
            throw new RuntimeException('Queue resolver did not return a Queue implementation.');
        }

        if (method_exists($command, 'queue')) {
            return $command->queue($queue, $command);
        }

        return $this->pushCommandToQueue($queue, $command);
    }

    /**
     * Push the command onto the given queue instance.
	 * 推入命令至给定的队列实例
     *
     * @param  \Illuminate\Contracts\Queue\Queue  $queue
     * @param  mixed  $command
     * @return mixed
     */
    protected function pushCommandToQueue($queue, $command)
    {
        if (isset($command->queue, $command->delay)) {
            return $queue->laterOn($command->queue, $command->delay, $command);
        }

        if (isset($command->queue)) {
            return $queue->pushOn($command->queue, $command);
        }

        if (isset($command->delay)) {
            return $queue->later($command->delay, $command);
        }

        return $queue->push($command);
    }

    /**
     * Dispatch a command to its appropriate handler after the current process.
	 * 分派命令给当前进程之后的处理程序
     *
     * @param  mixed  $command
     * @param  mixed  $handler
     * @return void
     */
    public function dispatchAfterResponse($command, $handler = null)
    {
        $this->container->terminating(function () use ($command, $handler) {
            $this->dispatchNow($command, $handler);
        });
    }

    /**
     * Set the pipes through which commands should be piped before dispatching.
	 * 设置命令应该通过的管道在调度之前
     *
     * @param  array  $pipes
     * @return $this
     */
    public function pipeThrough(array $pipes)
    {
        $this->pipes = $pipes;

        return $this;
    }

    /**
     * Map a command to a handler.
	 * 映射命令到处理程序
     *
     * @param  array  $map
     * @return $this
     */
    public function map(array $map)
    {
        $this->handlers = array_merge($this->handlers, $map);

        return $this;
    }
}

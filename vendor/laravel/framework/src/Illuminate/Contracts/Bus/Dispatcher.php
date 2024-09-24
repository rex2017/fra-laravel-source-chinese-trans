<?php
/**
 * 契约，总线调度接口
 */

namespace Illuminate\Contracts\Bus;

interface Dispatcher
{
    /**
     * Dispatch a command to its appropriate handler.
	 * 分派命令给相应的处理程序
     *
     * @param  mixed  $command
     * @return mixed
     */
    public function dispatch($command);

    /**
     * Dispatch a command to its appropriate handler in the current process.
	 * 分派命令给当前进程中相应的处理程序
     *
     * @param  mixed  $command
     * @param  mixed  $handler
     * @return mixed
     */
    public function dispatchNow($command, $handler = null);

    /**
     * Determine if the given command has a handler.
	 * 确定命令是否有处理程序
     *
     * @param  mixed  $command
     * @return bool
     */
    public function hasCommandHandler($command);

    /**
     * Retrieve the handler for a command.
	 * 检索命令的处理程序
     *
     * @param  mixed  $command
     * @return bool|mixed
     */
    public function getCommandHandler($command);

    /**
     * Set the pipes commands should be piped through before dispatching.
	 * 设置通过管道的命令在调度前
     *
     * @param  array  $pipes
     * @return $this
     */
    public function pipeThrough(array $pipes);

    /**
     * Map a command to a handler.
	 * 命令处理程序映射
     *
     * @param  array  $map
     * @return $this
     */
    public function map(array $map);
}

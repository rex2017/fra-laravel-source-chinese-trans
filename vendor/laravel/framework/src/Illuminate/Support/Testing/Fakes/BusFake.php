<?php
/**
 * 支持，总线伪造
 */

namespace Illuminate\Support\Testing\Fakes;

use Closure;
use Illuminate\Contracts\Bus\Dispatcher;
use Illuminate\Support\Arr;
use PHPUnit\Framework\Assert as PHPUnit;

class BusFake implements Dispatcher
{
    /**
     * The original Bus dispatcher implementation.
	 * 原始总线调度程序实现
     *
     * @var \Illuminate\Contracts\Bus\Dispatcher
     */
    protected $dispatcher;

    /**
     * The job types that should be intercepted instead of dispatched.
	 * 应该拦截而不是分派的作业类型
     *
     * @var array
     */
    protected $jobsToFake;

    /**
     * The commands that have been dispatched.
	 * 已调度的命令
     *
     * @var array
     */
    protected $commands = [];

    /**
     * The commands that have been dispatched after the response has been sent.
	 * 在响应发送后已分派的命令
     *
     * @var array
     */
    protected $commandsAfterResponse = [];

    /**
     * Create a new bus fake instance.
	 * 创建新的总线伪实例
     *
     * @param  \Illuminate\Contracts\Bus\Dispatcher  $dispatcher
     * @param  array|string  $jobsToFake
     * @return void
     */
    public function __construct(Dispatcher $dispatcher, $jobsToFake = [])
    {
        $this->dispatcher = $dispatcher;

        $this->jobsToFake = Arr::wrap($jobsToFake);
    }

    /**
     * Assert if a job was dispatched based on a truth-test callback.
	 * 断言作业是否基于真值测试回调进行分派
     *
     * @param  string  $command
     * @param  callable|int|null  $callback
     * @return void
     */
    public function assertDispatched($command, $callback = null)
    {
        if (is_numeric($callback)) {
            return $this->assertDispatchedTimes($command, $callback);
        }

        PHPUnit::assertTrue(
            $this->dispatched($command, $callback)->count() > 0 ||
            $this->dispatchedAfterResponse($command, $callback)->count() > 0,
            "The expected [{$command}] job was not dispatched."
        );
    }

    /**
     * Assert if a job was pushed a number of times.
	 * 判断一个作业是否被推送了多次
     *
     * @param  string  $command
     * @param  int  $times
     * @return void
     */
    public function assertDispatchedTimes($command, $times = 1)
    {
        $count = $this->dispatched($command)->count() +
                 $this->dispatchedAfterResponse($command)->count();

        PHPUnit::assertTrue(
            $count === $times,
            "The expected [{$command}] job was pushed {$count} times instead of {$times} times."
        );
    }

    /**
     * Determine if a job was dispatched based on a truth-test callback.
	 * 确定是否根据true-test回调分派了作业
     *
     * @param  string  $command
     * @param  callable|null  $callback
     * @return void
     */
    public function assertNotDispatched($command, $callback = null)
    {
        PHPUnit::assertTrue(
            $this->dispatched($command, $callback)->count() === 0 &&
            $this->dispatchedAfterResponse($command, $callback)->count() === 0,
            "The unexpected [{$command}] job was dispatched."
        );
    }

    /**
     * Assert if a job was dispatched after the response was sent based on a truth-test callback.
	 * 根据true -test回调发送响应后，判断是否分派了作业。
     *
     * @param  string  $command
     * @param  callable|int|null  $callback
     * @return void
     */
    public function assertDispatchedAfterResponse($command, $callback = null)
    {
        if (is_numeric($callback)) {
            return $this->assertDispatchedAfterResponseTimes($command, $callback);
        }

        PHPUnit::assertTrue(
            $this->dispatchedAfterResponse($command, $callback)->count() > 0,
            "The expected [{$command}] job was not dispatched for after sending the response."
        );
    }

    /**
     * Assert if a job was pushed after the response was sent a number of times.
	 * 断言在发送响应多次后是否推送了作业
     *
     * @param  string  $command
     * @param  int  $times
     * @return void
     */
    public function assertDispatchedAfterResponseTimes($command, $times = 1)
    {
        PHPUnit::assertTrue(
            ($count = $this->dispatchedAfterResponse($command)->count()) === $times,
            "The expected [{$command}] job was pushed {$count} times instead of {$times} times."
        );
    }

    /**
     * Determine if a job was dispatched based on a truth-test callback.
	 * 确定是否根据true-test回调分派了作业
     *
     * @param  string  $command
     * @param  callable|null  $callback
     * @return void
     */
    public function assertNotDispatchedAfterResponse($command, $callback = null)
    {
        PHPUnit::assertTrue(
            $this->dispatchedAfterResponse($command, $callback)->count() === 0,
            "The unexpected [{$command}] job was dispatched for after sending the response."
        );
    }

    /**
     * Get all of the jobs matching a truth-test callback.
	 * 得到所有符合真实测试回调的工作
     *
     * @param  string  $command
     * @param  callable|null  $callback
     * @return \Illuminate\Support\Collection
     */
    public function dispatched($command, $callback = null)
    {
        if (! $this->hasDispatched($command)) {
            return collect();
        }

        $callback = $callback ?: function () {
            return true;
        };

        return collect($this->commands[$command])->filter(function ($command) use ($callback) {
            return $callback($command);
        });
    }

    /**
     * Get all of the jobs dispatched after the response was sent matching a truth-test callback.
	 * 得到响应发送后与true-test回调相匹配的所有作业。
     *
     * @param  string  $command
     * @param  callable|null  $callback
     * @return \Illuminate\Support\Collection
     */
    public function dispatchedAfterResponse(string $command, $callback = null)
    {
        if (! $this->hasDispatchedAfterResponse($command)) {
            return collect();
        }

        $callback = $callback ?: function () {
            return true;
        };

        return collect($this->commandsAfterResponse[$command])->filter(function ($command) use ($callback) {
            return $callback($command);
        });
    }

    /**
     * Determine if there are any stored commands for a given class.
	 * 确定是否有任何针对给定类的存储命令
     *
     * @param  string  $command
     * @return bool
     */
    public function hasDispatched($command)
    {
        return isset($this->commands[$command]) && ! empty($this->commands[$command]);
    }

    /**
     * Determine if there are any stored commands for a given class.
	 * 确定是否有任何针对给定类的存储命令
     *
     * @param  string  $command
     * @return bool
     */
    public function hasDispatchedAfterResponse($command)
    {
        return isset($this->commandsAfterResponse[$command]) && ! empty($this->commandsAfterResponse[$command]);
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
        if ($this->shouldFakeJob($command)) {
            $this->commands[get_class($command)][] = $command;
        } else {
            return $this->dispatcher->dispatch($command);
        }
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
        if ($this->shouldFakeJob($command)) {
            $this->commands[get_class($command)][] = $command;
        } else {
            return $this->dispatcher->dispatchNow($command, $handler);
        }
    }

    /**
     * Dispatch a command to its appropriate handler.
	 * 分派命令给相应的处理程序
     *
     * @param  mixed  $command
     * @return mixed
     */
    public function dispatchAfterResponse($command)
    {
        if ($this->shouldFakeJob($command)) {
            $this->commandsAfterResponse[get_class($command)][] = $command;
        } else {
            return $this->dispatcher->dispatch($command);
        }
    }

    /**
     * Determine if an command should be faked or actually dispatched.
	 * 确定是伪造命令还是实际分派命令
     *
     * @param  mixed  $command
     * @return bool
     */
    protected function shouldFakeJob($command)
    {
        if (empty($this->jobsToFake)) {
            return true;
        }

        return collect($this->jobsToFake)
            ->filter(function ($job) use ($command) {
                return $job instanceof Closure
                            ? $job($command)
                            : $job === get_class($command);
            })->isNotEmpty();
    }

    /**
     * Set the pipes commands should be piped through before dispatching.
	 * 设置调度前需要通过管道的命令
     *
     * @param  array  $pipes
     * @return $this
     */
    public function pipeThrough(array $pipes)
    {
        $this->dispatcher->pipeThrough($pipes);

        return $this;
    }

    /**
     * Determine if the given command has a handler.
	 * 确定给定命令是否有处理程序
     *
     * @param  mixed  $command
     * @return bool
     */
    public function hasCommandHandler($command)
    {
        return $this->dispatcher->hasCommandHandler($command);
    }

    /**
     * Retrieve the handler for a command.
	 * 检索命令的处理程序
     *
     * @param  mixed  $command
     * @return mixed
     */
    public function getCommandHandler($command)
    {
        return $this->dispatcher->getCommandHandler($command);
    }

    /**
     * Map a command to a handler.
	 * 将命令映射到处理程序
     *
     * @param  array  $map
     * @return $this
     */
    public function map(array $map)
    {
        $this->dispatcher->map($map);

        return $this;
    }
}

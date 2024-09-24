<?php
/**
 * 控制台，计划表
 */

namespace Illuminate\Console\Scheduling;

use Closure;
use DateTimeInterface;
use Illuminate\Console\Application;
use Illuminate\Container\Container;
use Illuminate\Contracts\Bus\Dispatcher;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\CallQueuedClosure;
use Illuminate\Support\ProcessUtils;
use Illuminate\Support\Str;
use Illuminate\Support\Traits\Macroable;
use RuntimeException;

class Schedule
{
    use Macroable;

    /**
     * All of the events on the schedule.
	 * 所有事件在计划表
     *
     * @var \Illuminate\Console\Scheduling\Event[]
     */
    protected $events = [];

    /**
     * The event mutex implementation.
	 * 事件互斥锁的实现
     *
     * @var \Illuminate\Console\Scheduling\EventMutex
     */
    protected $eventMutex;

    /**
     * The scheduling mutex implementation.
	 * 调度互斥的实现
     *
     * @var \Illuminate\Console\Scheduling\SchedulingMutex
     */
    protected $schedulingMutex;

    /**
     * The timezone the date should be evaluated on.
	 * 时区应该对日期进行评估
     *
     * @var \DateTimeZone|string
     */
    protected $timezone;

    /**
     * The job dispatcher implementation.
	 * 作业调度器实现
     *
     * @var \Illuminate\Contracts\Bus\Dispatcher
     */
    protected $dispatcher;

    /**
     * Create a new schedule instance.
	 * 创建新的计划实例
     *
     * @param  \DateTimeZone|string|null  $timezone
     * @return void
     */
    public function __construct($timezone = null)
    {
        $this->timezone = $timezone;

        if (! class_exists(Container::class)) {
            throw new RuntimeException(
                'A container implementation is required to use the scheduler. Please install the illuminate/container package.'
            );
        }

        $container = Container::getInstance();

        $this->eventMutex = $container->bound(EventMutex::class)
                                ? $container->make(EventMutex::class)
                                : $container->make(CacheEventMutex::class);

        $this->schedulingMutex = $container->bound(SchedulingMutex::class)
                                ? $container->make(SchedulingMutex::class)
                                : $container->make(CacheSchedulingMutex::class);
    }

    /**
     * Add a new callback event to the schedule.
	 * 添加新的回调事件到计划
     *
     * @param  string|callable  $callback
     * @param  array  $parameters
     * @return \Illuminate\Console\Scheduling\CallbackEvent
     */
    public function call($callback, array $parameters = [])
    {
        $this->events[] = $event = new CallbackEvent(
            $this->eventMutex, $callback, $parameters, $this->timezone
        );

        return $event;
    }

    /**
     * Add a new Artisan command event to the schedule.
	 * 添加一个新的Artisan命令事件到计划
     *
     * @param  string  $command
     * @param  array  $parameters
     * @return \Illuminate\Console\Scheduling\Event
     */
    public function command($command, array $parameters = [])
    {
        if (class_exists($command)) {
            $command = Container::getInstance()->make($command)->getName();
        }

        return $this->exec(
            Application::formatCommandString($command), $parameters
        );
    }

    /**
     * Add a new job callback event to the schedule.
	 * 添加一个新的作业回调事件到计划
     *
     * @param  object|string  $job
     * @param  string|null  $queue
     * @param  string|null  $connection
     * @return \Illuminate\Console\Scheduling\CallbackEvent
     */
    public function job($job, $queue = null, $connection = null)
    {
        return $this->call(function () use ($job, $queue, $connection) {
            $job = is_string($job) ? Container::getInstance()->make($job) : $job;

            if ($job instanceof ShouldQueue) {
                $this->dispatchToQueue($job, $queue ?? $job->queue, $connection ?? $job->connection);
            } else {
                $this->dispatchNow($job);
            }
        })->name(is_string($job) ? $job : get_class($job));
    }

    /**
     * Dispatch the given job to the queue.
	 * 分派给定的作业到队列
     *
     * @param  object  $job
     * @param  string|null  $queue
     * @param  string|null  $connection
     * @return void
     */
    protected function dispatchToQueue($job, $queue, $connection)
    {
        if ($job instanceof Closure) {
            if (! class_exists(CallQueuedClosure::class)) {
                throw new RuntimeException(
                    'To enable support for closure jobs, please install the illuminate/queue package.'
                );
            }

            $job = CallQueuedClosure::create($job);
        }

        $this->getDispatcher()->dispatch(
            $job->onConnection($connection)->onQueue($queue)
        );
    }

    /**
     * Dispatch the given job right now.
	 * 调度给定的任务立即
     *
     * @param  object  $job
     * @return void
     */
    protected function dispatchNow($job)
    {
        $this->getDispatcher()->dispatchNow($job);
    }

    /**
     * Add a new command event to the schedule.
	 * 添加一个新的命令事件到计划
     *
     * @param  string  $command
     * @param  array  $parameters
     * @return \Illuminate\Console\Scheduling\Event
     */
    public function exec($command, array $parameters = [])
    {
        if (count($parameters)) {
            $command .= ' '.$this->compileParameters($parameters);
        }

        $this->events[] = $event = new Event($this->eventMutex, $command, $this->timezone);

        return $event;
    }

    /**
     * Compile parameters for a command.
	 * 编译命令的参数
     *
     * @param  array  $parameters
     * @return string
     */
    protected function compileParameters(array $parameters)
    {
        return collect($parameters)->map(function ($value, $key) {
            if (is_array($value)) {
                return $this->compileArrayInput($key, $value);
            }

            if (! is_numeric($value) && ! preg_match('/^(-.$|--.*)/i', $value)) {
                $value = ProcessUtils::escapeArgument($value);
            }

            return is_numeric($key) ? $value : "{$key}={$value}";
        })->implode(' ');
    }

    /**
     * Compile array input for a command.
	 * 编译命令的数组输入
     *
     * @param  string|int  $key
     * @param  array  $value
     * @return string
     */
    public function compileArrayInput($key, $value)
    {
        $value = collect($value)->map(function ($value) {
            return ProcessUtils::escapeArgument($value);
        });

        if (Str::startsWith($key, '--')) {
            $value = $value->map(function ($value) use ($key) {
                return "{$key}={$value}";
            });
        } elseif (Str::startsWith($key, '-')) {
            $value = $value->map(function ($value) use ($key) {
                return "{$key} {$value}";
            });
        }

        return $value->implode(' ');
    }

    /**
     * Determine if the server is allowed to run this event.
	 * 确定是否允许服务器运行此事件
     *
     * @param  \Illuminate\Console\Scheduling\Event  $event
     * @param  \DateTimeInterface  $time
     * @return bool
     */
    public function serverShouldRun(Event $event, DateTimeInterface $time)
    {
        return $this->schedulingMutex->create($event, $time);
    }

    /**
     * Get all of the events on the schedule that are due.
	 * 把所有要做的事情都写在日程表上
     *
     * @param  \Illuminate\Contracts\Foundation\Application  $app
     * @return \Illuminate\Support\Collection
     */
    public function dueEvents($app)
    {
        return collect($this->events)->filter->isDue($app);
    }

    /**
     * Get all of the events on the schedule.
	 * 得到所有事件在计划
     *
     * @return \Illuminate\Console\Scheduling\Event[]
     */
    public function events()
    {
        return $this->events;
    }

    /**
     * Specify the cache store that should be used to store mutexes.
	 * 指定应该用于存储互斥锁的缓存存储
     *
     * @param  string  $store
     * @return $this
     */
    public function useCache($store)
    {
        if ($this->eventMutex instanceof CacheEventMutex) {
            $this->eventMutex->useStore($store);
        }

        if ($this->schedulingMutex instanceof CacheSchedulingMutex) {
            $this->schedulingMutex->useStore($store);
        }

        return $this;
    }

    /**
     * Get the job dispatcher, if available.
	 * 获取作业调度器，如果可用
     *
     * @return \Illuminate\Contracts\Bus\Dispatcher
     */
    protected function getDispatcher()
    {
        if ($this->dispatcher === null) {
            try {
                $this->dispatcher = Container::getInstance()->make(Dispatcher::class);
            } catch (BindingResolutionException $e) {
                throw new RuntimeException(
                    'Unable to resolve the dispatcher from the service container. Please bind it or install the illuminate/bus package.',
                    $e->getCode(), $e
                );
            }
        }

        return $this->dispatcher;
    }
}

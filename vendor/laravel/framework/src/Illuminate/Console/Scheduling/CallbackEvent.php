<?php
/**
 * 控制台，回调事件
 */

namespace Illuminate\Console\Scheduling;

use Illuminate\Contracts\Container\Container;
use Illuminate\Support\Reflector;
use InvalidArgumentException;
use LogicException;

class CallbackEvent extends Event
{
    /**
     * The callback to call.
	 * 回调请求
     *
     * @var string
     */
    protected $callback;

    /**
     * The parameters to pass to the method.
	 * 传递给方法的参数
     *
     * @var array
     */
    protected $parameters;

    /**
     * Create a new event instance.
	 * 创建新的事件实例
     *
     * @param  \Illuminate\Console\Scheduling\EventMutex  $mutex
     * @param  string  $callback
     * @param  array  $parameters
     * @param  \DateTimeZone|string|null  $timezone
     * @return void
     *
     * @throws \InvalidArgumentException
     */
    public function __construct(EventMutex $mutex, $callback, array $parameters = [], $timezone = null)
    {
        if (! is_string($callback) && ! Reflector::isCallable($callback)) {
            throw new InvalidArgumentException(
                'Invalid scheduled callback event. Must be a string or callable.'
            );
        }

        $this->mutex = $mutex;
        $this->callback = $callback;
        $this->parameters = $parameters;
        $this->timezone = $timezone;
    }

    /**
     * Run the given event.
	 * 运行给定事件
     *
     * @param  \Illuminate\Contracts\Container\Container  $container
     * @return mixed
     *
     * @throws \Exception
     */
    public function run(Container $container)
    {
        if ($this->description && $this->withoutOverlapping &&
            ! $this->mutex->create($this)) {
            return;
        }

        $pid = getmypid();

        register_shutdown_function(function () use ($pid) {
            if ($pid === getmypid()) {
                $this->removeMutex();
            }
        });

        parent::callBeforeCallbacks($container);

        try {
            $response = is_object($this->callback)
                        ? $container->call([$this->callback, '__invoke'], $this->parameters)
                        : $container->call($this->callback, $this->parameters);
        } finally {
            $this->removeMutex();

            parent::callAfterCallbacks($container);
        }

        return $response;
    }

    /**
     * Clear the mutex for the event.
	 * 清除事件的互斥锁
     *
     * @return void
     */
    protected function removeMutex()
    {
        if ($this->description && $this->withoutOverlapping) {
            $this->mutex->forget($this);
        }
    }

    /**
     * Do not allow the event to overlap each other.
	 * 不要让事件相互重叠
     *
     * @param  int  $expiresAt
     * @return $this
     *
     * @throws \LogicException
     */
    public function withoutOverlapping($expiresAt = 1440)
    {
        if (! isset($this->description)) {
            throw new LogicException(
                "A scheduled event name is required to prevent overlapping. Use the 'name' method before 'withoutOverlapping'."
            );
        }

        $this->withoutOverlapping = true;

        $this->expiresAt = $expiresAt;

        return $this->skip(function () {
            return $this->mutex->exists($this);
        });
    }

    /**
     * Allow the event to only run on one server for each cron expression.
	 * 允许事件仅在一台服务器上运行，对于每个cron表达式
     *
     * @return $this
     *
     * @throws \LogicException
     */
    public function onOneServer()
    {
        if (! isset($this->description)) {
            throw new LogicException(
                "A scheduled event name is required to only run on one server. Use the 'name' method before 'onOneServer'."
            );
        }

        $this->onOneServer = true;

        return $this;
    }

    /**
     * Get the mutex name for the scheduled command.
	 * 得到计划命令的互斥对象名称
     *
     * @return string
     */
    public function mutexName()
    {
        return 'framework/schedule-'.sha1($this->description);
    }

    /**
     * Get the summary of the event for display.
	 * 得到要显示的事件摘要
     *
     * @return string
     */
    public function getSummaryForDisplay()
    {
        if (is_string($this->description)) {
            return $this->description;
        }

        return is_string($this->callback) ? $this->callback : 'Callback';
    }
}
